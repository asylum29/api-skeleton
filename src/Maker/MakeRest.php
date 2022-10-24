<?php

namespace App\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Inflector\InflectorFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

final class MakeRest extends AbstractMaker
{
    private $doctrineHelper;
    private $controllerClassName;
    private $crudClassName;
    private $inflector;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        if (class_exists(InflectorFactory::class)) {
            $this->inflector = InflectorFactory::create()->build();
        }
    }

    public static function getCommandName(): string
    {
        return 'make:rest';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates rest CRUD for Doctrine entity class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument(
                'entity-class',
                InputArgument::OPTIONAL,
                sprintf(
                    'The class name of the entity to create CRUD (e.g. <fg=yellow>%s</>)',
                    Str::asClassName(Str::getRandomTerm())
                )
            )
        ;
        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists(
                $input->getArgument('entity-class'),
                $this->doctrineHelper->getEntitiesForAutocomplete()
            ),
            'Entity\\'
        );
        $entityDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($entityClassDetails->getFullName());
        $entityMetadata = $this->doctrineHelper->getMetadata($entityClassDetails->getFullName());
        $entityMethods = $entityMetadata->getReflectionClass()->getMethods();
        $entitySetters = array_filter($entityMethods, static function ($method) {
            return 0 === strpos($method->getName(), 'set');
        });
        $entityIdentifierPattern = '.+';
        if ('integer' === $entityMetadata->getTypeOfField($entityDoctrineDetails->getIdentifier())) {
            $entityIdentifierPattern = "\d+";
        }

        $controllerClassDetails = $generator->createClassNameDetails(
            $this->controllerClassName,
            'Controller\\',
            'Controller'
        );
        $repositoryClassDetails = $generator->createClassNameDetails(
            '\\'.$entityDoctrineDetails->getRepositoryClass(),
            'Repository\\',
            'Repository'
        );
        $entityVarPlural = lcfirst($this->pluralize($entityClassDetails->getShortName()));
        $entityVarSingular = lcfirst($this->singularize($entityClassDetails->getShortName()));
        $routeName = Str::asRouteName($controllerClassDetails->getRelativeNameWithoutSuffix());

        $crudClassDetails = $generator->createClassNameDetails(
            '\\App\\Service\\'.$this->crudClassName,
            'Service\\',
            'Service'
        );

        $generator->generateClass(
            $crudClassDetails->getFullName(),
            'skeleton/CrudManager',
            [
                'entity_full_class_name' => $entityClassDetails->getFullName(),
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_var_singular' => $entityVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_setters' => $entitySetters,
                'repository_full_class_name' => $repositoryClassDetails->getFullName(),
                'repository_class_name' => $repositoryClassDetails->getShortName(),
            ]
        );

        $generator->generateController(
            $controllerClassDetails->getFullName(),
            'skeleton/RestController',
            [
                'crud_full_class_name' => $crudClassDetails->getFullName(),
                'crud_class_name' => $crudClassDetails->getShortName(),
                'entity_full_class_name' => $entityClassDetails->getFullName(),
                'entity_class_name' => $entityClassDetails->getShortName(),
                'route_path' => Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix()),
                'route_name' => $routeName,
                'entity_var_plural' => $entityVarPlural,
                'entity_var_singular' => $entityVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_identifier_pattern' => $entityIdentifierPattern,
                'entity_setters' => $entitySetters,
                'repository_full_class_name' => $repositoryClassDetails->getFullName(),
                'repository_class_name' => $repositoryClassDetails->getShortName(),
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
        $io->text(sprintf(
            'Next: Check your new REST by going to <fg=yellow>%s/</>',
            Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix()))
        );
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($entities);

            $value = $io->askQuestion($question);

            $input->setArgument('entity-class', $value);
        }

        $defaultControllerClass = Str::asClassName(sprintf(
            '%s Controller',
            $input->getArgument('entity-class')
        ));

        $this->controllerClassName = $io->ask(
            sprintf('Choose a name for your controller class (e.g. <fg=yellow>%s</>)', $defaultControllerClass),
            $defaultControllerClass
        );

        $defaultCrudClass = Str::asClassName(sprintf(
            '%s Manager',
            $input->getArgument('entity-class')
        ));

        $this->crudClassName = $io->ask(
            sprintf('Choose a name for your CRUD class for entity (e.g. <fg=yellow>%s</>)', $defaultCrudClass),
            $defaultCrudClass
        );
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Route::class,
            'router'
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm-pack'
        );

        $dependencies->addClassDependency(
            CsrfTokenManager::class,
            'security-csrf'
        );

        $dependencies->addClassDependency(
            ParamConverter::class,
            'annotations'
        );
    }

    private function pluralize(string $word): string
    {
        if (null !== $this->inflector) {
            return $this->inflector->pluralize($word);
        }

        return $this->inflector->pluralize($word);
    }

    private function singularize(string $word): string
    {
        if (null !== $this->inflector) {
            return $this->inflector->singularize($word);
        }

        return $this->inflector->singularize($word);
    }
}

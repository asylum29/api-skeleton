<?php

namespace App\Controller;

use App\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseController extends AbstractController
{
    /** @var ValidatorInterface */
    protected $validator;
    /** @var SerializerInterface */
    protected $serializer;

    /**
     * @required
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * @required
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    protected function success($data = null, array $meta = []): JsonResponse
    {
        $meta['success'] = true;
        $responseData = $this->serializer->serialize($data, 'api', $meta);
        $response = new JsonResponse();
        $response->setContent($responseData);
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }

    protected function error($message = 'An error occurred', $statusCode = Response::HTTP_BAD_REQUEST): void
    {
        throw new ApiException($message, $statusCode);
    }

    protected function validate($obj, array $groups = null): void
    {
        $errors = $this->validator->validate($obj, null, $groups);
        if (count($errors) > 0) {
            $message = [];
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $message[] = $error->getMessage();
            }
            $this->error(implode("\n", $message));
        }
    }
}

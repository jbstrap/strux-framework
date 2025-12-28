<?php

declare(strict_types=1);

namespace Strux\Component\Http\Controller\Api;

use Strux\Component\Http\ApiResponse;
use Strux\Component\Http\Controller\Web\Controller as WebController;

abstract class Controller extends WebController
{
    /**
     * Returns a 200 OK response.
     */
    protected function Ok(mixed $data = null, string $message = 'Success'): ApiResponse
    {
        return new ApiResponse(200, $data, $message);
    }

    /**
     * Returns a 201 Created response.
     */
    protected function Created(mixed $data = null, string $message = 'Resource created successfully'): ApiResponse
    {
        return new ApiResponse(201, $data, $message);
    }

    /**
     * Returns a 204 No Content response.
     */
    protected function NoContent(): ApiResponse
    {
        return new ApiResponse(204, null, 'No content');
    }

    /**
     * Returns a 400 Bad Request response.
     */
    protected function BadRequest(string $message = 'Bad Request', ?array $errors = null): ApiResponse
    {
        return new ApiResponse(400, null, $message, $errors);
    }

    /**
     * Returns a 401 Unauthorized response.
     */
    protected function Unauthorized(string $message = 'Unauthorized'): ApiResponse
    {
        return new ApiResponse(401, null, $message);
    }

    /**
     * Returns a 403 Forbidden response.
     */
    protected function Forbidden(string $message = 'Forbidden'): ApiResponse
    {
        return new ApiResponse(403, null, $message);
    }

    /**
     * Returns a 404 Not Found response.
     */
    protected function NotFound(string $message = 'Resource not found'): ApiResponse
    {
        return new ApiResponse(404, null, $message);
    }

    /**
     * Returns a 422 Unprocessable Entity response, typically for validation errors.
     *
     * @param array $errors An array of validation errors.
     * @param string $message A summary message.
     * @return \Strux\Component\Http\ApiResponse
     */
    protected function UnprocessableEntity(array $errors, string $message = 'The given data was invalid.'): ApiResponse
    {
        return new ApiResponse(422, null, $message, $errors);
    }
}
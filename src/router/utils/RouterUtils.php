<?php

namespace utils\router\utils;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Pecee\Http\Input\InputHandler;
use Pecee\Http\Request;
use Pecee\Http\Response;
use Pecee\SimpleRouter\SimpleRouter as Router;

trait RouterUtils{

    public static string $successResponseGenerator = 'utils\\router\\utils\\RouterUtils::successResponseGenerator';

    public static function successResponseGenerator(mixed $data): array{
        return array(
            'success' => 'bool',
            'message' => 'string',
            'data' => $data,
            'meta' => array()
        );
    }

    public static string $errorResponseGenerator = 'utils\\router\\utils\\RouterUtils::errorResponseGenerator';

    public static function errorResponseGenerator(mixed $data): array{
        return array(
            'success' => 'bool',
            'message' => 'string',
            'info' => 'string',
            'errors' => array(),
            'code' => 'integer',
            'data' => $data
        );
    }

    public static string $successPaginationResponseGenerator = 'utils\\router\\utils\\RouterUtils::successPaginationResponseGenerator';

    public static function successPaginationResponseGenerator(mixed $data): array{
        return array(
            'success' => 'bool',
            'message' => 'string',
            'info' => 'string',
            'errors' => array(),
            'code' => 'integer',
            'data' => $data,
            'meta' => array(
                'current_page' => 'integer',
                'first_page' => 'integer',
                'last_page' => 'integer',
                'total_results' => 'integer',
                'page_size' => 'integer'
            )
        );
    }

    /**
     * @param array $data
     * @param string|null $message
     * @param array $meta
     */
    #[NoReturn]
    protected function success(array $data = array(), string $message = null, array $meta = array()){
        $this->response()->json(array(
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta
        ));
    }

    /**
     * @param array $data
     * @param int $current_page
     * @param int $page_size
     * @param int|null $total_results
     * @param string|null $message
     * @param array $meta
     * @return void
     */
    #[NoReturn]
    protected function successPagination(array $data, int $current_page, int $page_size, int $total_results = null, string $message = null, array $meta = array()){
        $this->success($data, $message, array_merge(array(
            'current_page' => $current_page,
            'first_page' => 1,
            'last_page' => ceil($total_results/$page_size),
            'total_results' => $total_results,
            'page_size' => $page_size
        ), $meta));
    }

    /**
     * @param string $modal
     * @param string|null $modal_id
     * @param array $data
     * @param string|null $message
     */
    #[NoReturn]
    protected function modal(string $modal, string $modal_id = null, array $data = array(), string $message = null){
        $this->response()->json(array(
            'success' => true,
            'message' => $message,
            'data' => $data,
            'modal' => $modal,
            'modal_id' => $modal_id
        ));
    }

    /**
     * @param string|null $message
     * @param int $code
     * @param array $data
     */
    #[NoReturn]
    protected function error(string $message = null, int $code = 500, array $data = array()){
        $this->response()->httpCode($code);
        $this->response()->json(array(
            'success' => false,
            'message' => $message,
            'info' => null,
            'errors' => array(),
            'code' => $code,
            'data' => $data
        ));
    }

    /**
     * @param Exception $exception
     * @param string|null $info
     * @param array $data
     * @return void
     */
    #[NoReturn]
    protected function exception(Exception $exception, string $info = null, array $data = array()){
        if(intval($exception->getCode()))
            $this->response()->httpCode($exception->getCode());
        $this->response()->json(array(
            'success' => false,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'info' => $info,
            'errors' => array(),
            'code' => $exception->getCode(),
            'data' => $data
        ));
    }

    /**
     * @param string|null $message
     * @param array $errors
     * @param int $code
     * @param array $data
     * @return void
     */
    #[NoReturn]
    protected function errors(string $message = null, array $errors = array(), int $code = 500, array $data = array()){
        $this->response()->httpCode($code);
        $this->response()->json(array(
            'success' => false,
            'message' => $message,
            'info' => null,
            'errors' => $errors,
            'code' => $code,
            'data' => $data
        ));
    }

    /**
     * @return Request
     */
    protected function request(): Request{
        return Router::request();
    }

    /**
     * @return Response
     */
    protected function response(): Response{
        return Router::response();
    }

    /**
     * @return InputHandler|null
     */
    protected function input(): ?InputHandler{
        return $this->request()->getInputHandler();
    }

    /**
     * @param string $url
     * @param int|null $code
     */
    #[NoReturn]
    protected function redirect(string $url, ?int $code = null): void{
        if($code !== null){
            $this->response()->httpCode($code);
        }

        $this->response()->redirect($url);
    }

}
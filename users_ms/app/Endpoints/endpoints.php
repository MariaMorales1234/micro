<?php

use App\Controllers\Repositories\UserControllerRepository;
use App\Controllers\UsersController;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

const listState = [
    1=>404,
    2=>409,
    'default'=> 400
];

$codeValidation = require __DIR__ . '/../Middlesware/CodeValidation.php';

return function(App $app)use ($codeValidation){
    $app->get('/', function (Request $request, Response $response, $args) {
        $response->getBody()->write("Hello world!");
        return $response;
    });

    $app->group('/users', function (RouteCollectorProxy $group) {

        $group->get('/', function (Request $request, Response $response){
            $usersController = new UsersController();
            $data = $usersController->index();
            $state = empty($data)?204 : 200;
            $response ->getBody()->write($data);
            return $response
                ->withHeader('Contect-Type', 'application/json')
                ->withStatus($state);
        });

        $group -> get('/{id}', function (Request $request, Response $response, $args) {
            try {
                $id = $args['id'];
                $usersController = new UsersController();
                $data = $usersController->detail($id);
                $response ->getBody()->write($data);
                return $response->withHeader('Contect-Type', 'application/json');
            }catch (Exception $ex){
                $code = $ex ->getCode();
                $state = listState[$code] ?? listState['default'];      
                return $response->withStatus($state);
            }
        });

        $group -> post('/', function (Request $request, Response $response){
            $bodyRequest = $request->getBody()->getContents();
            $dataRequest = json_decode("$bodyRequest", true);
            $usersController = new UsersController();
            $data = $usersController->create($dataRequest);
            $response ->getBody()->write($data);
            return $response->withHeader('Contect-Type', 'application/json');
        });

        $group -> put('/{id}', function(Request $request, Response $response, $args){
            $id = $args['id'];
            $bodyRequest = $request->getBody()->getContents();
            $dataRequest = json_decode($bodyRequest, true);
            $controller = new UsersController();
            $data = $controller ->update($id, $dataRequest);
            $response-> getBody()->write($data);
            return $response->withHeader('Content-Type', 'application/json');
        });

        $group -> delete('/{id}', function(Request $request, Response $response, $args){
            try {
                $id = $args['id'];
                $controller = new UsersController();
                $data = $controller ->delete($id);
                return $response->withHeader('Content-Type', 'application/json');
            }catch (Exception $ex){
                $code = $ex->getCode();
                $state = listState[$code] ?? listState['default'];
                return $response -> withStatus($state);
            }
        })->add(function ($req, $handler) {
            $token = $req->getHeaderLine('Code-Validation');
            if ($token !== '789') {
                $res = new \Slim\Psr7\Response();
                $res->getBody()
                    ->write(json_encode(['msg' => 'error']));
                return $res->withStatus(401);
            }
            return $handler->handle($req);
        });
    });

    $app->group('/users-v2', function (RouteCollectorProxy $group) use ($codeValidation) {
        $group->get('/',  [UserControllerRepository::class , 'index']);
        //$group->get('/v2', UsersControllerRespository::class . ':index');
        $group->get('/{id}',  [UserControllerRepository::class , 'detail']);
        $group->post('/',  [UserControllerRepository::class , 'create']);
        $group->put('/{id}',  [UserControllerRepository::class , 'update'])
            ->add($codeValidation);
    });
};

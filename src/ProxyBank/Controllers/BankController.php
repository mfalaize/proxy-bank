<?php


namespace ProxyBank\Controllers;


use ProxyBank\Exceptions\EmptyParsedBodyException;
use ProxyBank\Exceptions\RequiredValueException;
use ProxyBank\Services\BankService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Info(title="ProxyBank API", version="0.1")
 * @OA\Tag(name="Bank")
 * @OA\Schema(schema="Error", type="object", @OA\Property(property="message", type="string"))
 */
class BankController
{
    /**
     * @Inject
     * @var BankService
     */
    private $bankService;

    /**
     * @OA\Get(
     *     path="/bank/list",
     *     summary="List all available banks",
     *     operationId="listBanks",
     *     tags={"Bank"},
     *     @OA\Response(
     *         response="200",
     *         description="OK",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Bank")
     *         )
     *     )
     * )
     */
    public function listBanks(Request $request, Response $response)
    {
        $list = $this->bankService->listAvailableBanks();
        $response->getBody()->write(json_encode($list));
        return $response->withHeader("Content-Type", "application/json");
    }

    /**
     * @OA\Post(
     *     path="/bank/{bankId}/token",
     *     summary="Generate ProxyBank encrypted token which contains specific bank authentication inputs",
     *     description="This operation is often called multiple times : <ol><li>The first time, you need to send your bank-specific authentication information. The response give you an encrypted token which is complete or incomplete. If it is incomplete you need to call the operation again.</li><li>If you need to call the operation a second time, you need to send the incomplete token and any additional information needed by your bank (and asked by the first message response) to get finally the complete token.</li></ol>",
     *     operationId="getAuthToken",
     *     tags={"Bank"},
     *     @OA\Parameter(
     *         name="bankId",
     *         description="Specific bank identifier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         description="Specific bank authentication informations AND/OR incomplete token",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", description="The incomplete token given by this webservice if any"),
     *             @OA\Property(property="Login", type="string"),
     *             @OA\Property(property="Password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(ref="#/components/schemas/TokenResult")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Content-Type header, empty request body, invalid token and/or missing input required by your specific bank",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication failed",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unknown bankId",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getAuthToken(Request $request, Response $response, array $args)
    {
        $params = $request->getParsedBody();

        $token = null;

        if (empty($params)) {
            throw new EmptyParsedBodyException();
        }

        if (isset($params["token"])) {
            $token = $this->bankService->getAuthTokenWithEncryptedToken($args["bankId"], $params["token"]);
        } else {
            $token = $this->bankService->getAuthTokenWithUnencryptedInputs($args["bankId"], $params);
        }

        $response->getBody()->write(json_encode($token));
        return $response->withHeader("Content-Type", "application/json");
    }

    /**
     * @OA\Post(
     *     path="/bank/{bankId}/account/list",
     *     summary="List available accounts for a specific bank",
     *     operationId="listAccounts",
     *     tags={"Bank"},
     *     @OA\Parameter(
     *         name="bankId",
     *         description="Specific bank identifier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         description="The complete token to authenticate with your bank",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"token"},
     *             @OA\Property(property="token", type="string", description="The complete token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Content-Type header, empty request body and/or invalid token",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication informations have expired",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unknown bankId",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function listAccounts(Request $request, Response $response, array $args)
    {
        $params = $request->getParsedBody();

        $token = null;

        if (empty($params) || empty($params["token"])) {
            throw new EmptyParsedBodyException();
        }

        $accounts = $this->bankService->listAccounts($args["bankId"], $params["token"]);

        $response->getBody()->write(json_encode($accounts));
        return $response->withHeader("Content-Type", "application/json");
    }

    /**
     * @OA\Post(
     *     path="/bank/{bankId}/account/lastTransactions",
     *     summary="List last transactions for a specific bank account",
     *     description="Note: This operation give amounts and balances without currencies. It is assumed that you know what currency is related to this specific account",
     *     operationId="fetchTransactions",
     *     tags={"Bank"},
     *     @OA\Parameter(
     *         name="bankId",
     *         description="Specific bank identifier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         description="The complete token to authenticate with your bank",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"token", "accountId"},
     *             @OA\Property(property="token", type="string", description="The complete token"),
     *             @OA\Property(property="accountId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Content-Type header, empty request body and/or invalid token",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication informations have expired",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unknown bankId or accountId",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function fetchTransactions(Request $request, Response $response, array $args)
    {
        $params = $request->getParsedBody();

        $token = null;

        if (empty($params) || empty($params["token"])) {
            throw new EmptyParsedBodyException();
        }

        if (empty($params["accountId"])) {
            throw new RequiredValueException("accountId");
        }

        $accounts = $this->bankService->fetchTransactions($args["bankId"], $params["accountId"], $params["token"]);

        $response->getBody()->write(json_encode($accounts));
        return $response->withHeader("Content-Type", "application/json");
    }
}

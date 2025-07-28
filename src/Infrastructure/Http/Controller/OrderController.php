<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Order\Application\Command\CreateOrderCommand;
use App\Order\Application\Command\AddItemCommand;
use App\Order\Application\Command\RemoveItemCommand;
use App\Order\Application\Command\ChangeStatusCommand;
use App\Order\Application\Query\GetOrderQuery;
use App\Infrastructure\Http\Dto\CreateOrderRequestDto;
use App\Infrastructure\Http\Dto\AddItemRequestDto;
use App\Infrastructure\Http\Dto\RemoveItemRequestDto;
use App\Infrastructure\Http\Dto\ChangeStatusRequestDto;
use App\Infrastructure\Http\Exception\ValidationException;
use App\Shared\Application\CommandBus;
use App\Shared\Application\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderController extends BaseController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('/api/orders', name: 'create_order', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            /** @var CreateOrderRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, CreateOrderRequestDto::class);

            $command = CreateOrderCommand::fromPrimitives($dto->currency);
            $orderId = $this->commandBus->dispatch($command);

            return new JsonResponse(['orderId' => $orderId->toString()], JsonResponse::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/orders/{id}', name: 'get_order', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $getOrderQuery = GetOrderQuery::fromPrimitives($id);
        $orderDto = $this->queryBus->dispatch($getOrderQuery);

        if (!$orderDto) {
            return new JsonResponse(['error' => 'Order not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($orderDto, JsonResponse::HTTP_OK);
    }

    #[Route('/api/orders/{id}/items', name: 'add_order_item', methods: ['POST'])]
    public function addItem(string $id, Request $request): JsonResponse
    {
        try {
            /** @var AddItemRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, AddItemRequestDto::class);

            $command = AddItemCommand::fromPrimitives(
                $id,
                $dto->productId,
                $dto->productName,
                $dto->quantity,
                $dto->unitPrice,
                $dto->currency
            );
            $this->commandBus->dispatch($command);

            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/orders/{id}/items', name: 'remove_order_item', methods: ['DELETE'])]
    public function removeItem(string $id, Request $request): JsonResponse
    {
        try {
            /** @var RemoveItemRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, RemoveItemRequestDto::class);

            $command = RemoveItemCommand::fromPrimitives($id, $dto->orderItemId);
            $this->commandBus->dispatch($command);

            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/orders/{id}/status', name: 'change_order_status', methods: ['PUT'])]
    public function changeStatus(string $id, Request $request): JsonResponse
    {
        try {
            /** @var ChangeStatusRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, ChangeStatusRequestDto::class);

            $command = ChangeStatusCommand::fromPrimitives($id, $dto->status);
            $this->commandBus->dispatch($command);

            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }
}
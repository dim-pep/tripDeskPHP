<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use App\Application\Trip\Exception\TripNotFoundException;
use App\Application\Trip\TripService;
use App\Domain\Trip\Trip;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use InvalidArgumentException;
use LogicException;

final readonly class TripController
{
    public function __construct(
        private TripService $tripService,
    ) {
    }

    public function index(Request $request): Response
    {
        $trips = $this->tripService->getAllTrips();

        return Response::json([
            'data' => array_map(
                fn (Trip $trip): array => $this->serializeTrip($trip),
                $trips,
            ),
        ]);
    }

    /**
     * @param array<string, string> $parameters
     */
    public function show(Request $request, array $parameters): Response
    {
        try {
            $id = $this->parseId($parameters);
            $trip = $this->tripService->getTrip($id);
        } catch (TripNotFoundException) {
            return Response::json(['error' => 'Trip not found'], 404);
        } catch (InvalidArgumentException) {
            return Response::json(['error' => 'Invalid trip ID'], 400);
        }

        return Response::json([
            'data' => $this->serializeTrip($trip),
        ]);
    }

    public function create(Request $request): Response
    {
        try {
            $city = $request->body['city'] ?? null;
            $ticketPrice = $request->body['ticket_price'] ?? null;
            $hotelPrice = $request->body['hotel_price'] ?? null;
            $nights = $request->body['nights'] ?? null;

            if (!is_string($city) || trim($city) === '') {
                throw new InvalidArgumentException('City is required.');
            }

            if (!is_int($ticketPrice) || $ticketPrice < 0) {
                throw new InvalidArgumentException(
                    'Ticket price must be a non-negative integer.'
                );
            }

            if (!is_int($hotelPrice) || $hotelPrice < 0) {
                throw new InvalidArgumentException(
                    'Hotel price must be a non-negative integer.'
                );
            }

            if (!is_int($nights) || $nights < 0) {
                throw new InvalidArgumentException(
                    'Nights must be a non-negative integer.'
                );
            }

            $trip = $this->tripService->createTrip(
                city: $city,
                ticketPrice: $ticketPrice,
                hotelPrice: $hotelPrice,
                nights: $nights,
            );
        } catch (InvalidArgumentException $exception) {
            return Response::json([
                'error' => $exception->getMessage(),
            ], 422);
        }

        return new Response(
            statusCode: 201,
            headers: [
                'Content-Type' => 'application/json; charset=utf-8',
                'Location' => '/api/trips/' . $trip->getId(),
            ],
            body: json_encode(
                ['data' => $this->serializeTrip($trip)],
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR,
            ),
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    public function approve(Request $request, array $parameters): Response
    {
        try {
            $id = $this->parseId($parameters);
            $trip = $this->tripService->approveTrip($id);
        } catch (TripNotFoundException) {
            return Response::json(['error' => 'Trip not found'], 404);
        } catch (InvalidArgumentException) {
            return Response::json(['error' => 'Invalid trip ID'], 400);
        } catch (LogicException $exception) {
            return Response::json([
                'error' => $exception->getMessage(),
            ], 409);
        }

        return Response::json([
            'data' => $this->serializeTrip($trip),
        ]);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function parseId(array $parameters): int
    {
        $rawId = $parameters['id'] ?? '';

        if (!ctype_digit($rawId) || (int) $rawId <= 0) {
            throw new InvalidArgumentException('Invalid trip ID.');
        }

        return (int) $rawId;
    }

    /**
     * @return array<string, int|string>
     */
    private function serializeTrip(Trip $trip): array
    {
        $id = $trip->getId();

        if ($id === null) {
            throw new LogicException('Cannot serialize a trip without an ID.');
        }

        return [
            'id' => $id,
            'city' => $trip->getCity(),
            'ticket_price' => $trip->getTicketPrice(),
            'hotel_price' => $trip->getHotelPrice(),
            'nights' => $trip->getNights(),
            'total_cost' => $trip->calculateTotalCost(),
            'status' => $trip->getStatus()->value,
        ];
    }
    /**
 * @param array<string, string> $parameters
 */
public function reject(Request $request, array $parameters): Response
{
    try {
        $id = $this->parseId($parameters);
        $trip = $this->tripService->rejectTrip($id);
    } catch (TripNotFoundException) {
        return Response::json(['error' => 'Trip not found'], 404);
    } catch (InvalidArgumentException) {
        return Response::json(['error' => 'Invalid trip ID'], 400);
    } catch (LogicException $exception) {
        return Response::json(['error' => $exception->getMessage()], 409);
    }

    return Response::json(['data' => $this->serializeTrip($trip)]);
}

/**
 * @param array<string, string> $parameters
 */
public function cancel(Request $request, array $parameters): Response
{
    try {
        $id = $this->parseId($parameters);
        $trip = $this->tripService->cancelTrip($id);
    } catch (TripNotFoundException) {
        return Response::json(['error' => 'Trip not found'], 404);
    } catch (InvalidArgumentException) {
        return Response::json(['error' => 'Invalid trip ID'], 400);
    } catch (LogicException $exception) {
        return Response::json(['error' => $exception->getMessage()], 409);
    }

    return Response::json(['data' => $this->serializeTrip($trip)]);
}

/**
 * @param array<string, string> $parameters
 */
public function update(Request $request, array $parameters): Response
{
    try {
        $id = $this->parseId($parameters);
    } catch (InvalidArgumentException) {
        return Response::json(['error' => 'Invalid trip ID'], 400);
    }

    try {
        $city = $request->body['city'] ?? null;
        $ticketPrice = $request->body['ticket_price'] ?? null;
        $hotelPrice = $request->body['hotel_price'] ?? null;
        $nights = $request->body['nights'] ?? null;

        if (!is_string($city) || trim($city) === '') {
            throw new InvalidArgumentException('City is required.');
        }

        if (!is_int($ticketPrice) || $ticketPrice < 0) {
            throw new InvalidArgumentException(
                'Ticket price must be a non-negative integer.'
            );
        }

        if (!is_int($hotelPrice) || $hotelPrice < 0) {
            throw new InvalidArgumentException(
                'Hotel price must be a non-negative integer.'
            );
        }

        if (!is_int($nights) || $nights < 0) {
            throw new InvalidArgumentException(
                'Nights must be a non-negative integer.'
            );
        }

        $trip = $this->tripService->updateTrip(
            id: $id,
            city: $city,
            ticketPrice: $ticketPrice,
            hotelPrice: $hotelPrice,
            nights: $nights,
        );
    } catch (TripNotFoundException) {
        return Response::json(['error' => 'Trip not found'], 404);
    } catch (InvalidArgumentException $exception) {
        return Response::json(['error' => $exception->getMessage()], 422);
    } catch (LogicException $exception) {
        return Response::json(['error' => $exception->getMessage()], 409);
    }

    return Response::json(['data' => $this->serializeTrip($trip)]);
}
}

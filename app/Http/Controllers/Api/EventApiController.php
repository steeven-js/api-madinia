<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;

class EventApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $events = Event::all();
        return response()->json($events);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = Event::create($request->validated());
        return response()->json($event, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $firebaseId): JsonResponse
    {
        $event = Event::where('firebaseId', $firebaseId)->first();
        return response()->json([
            'data' => $event,
            'exists' => !is_null($event)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, string $firebaseId): JsonResponse
    {
        try {
            $event = Event::where('firebaseId', $firebaseId)->first();

            if (!$event) {
                return response()->json([
                    'exists' => false,
                    'message' => 'Event not found'
                ], 404);
            }

            Log::info('Update Event Request', [
                'firebaseId' => $firebaseId,
                'request_data' => $request->all(),
                'validated_data' => $request->validated()
            ]);

            $validated = $request->validated();

            // Assurons-nous que les dates sont au bon format
            if (isset($validated['scheduled_date'])) {
                $validated['scheduled_date'] = date('Y-m-d H:i:s', strtotime($validated['scheduled_date']));
            }

            $event->update($validated);

            return response()->json([
                'data' => $event->fresh(),
                'exists' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Event Update Error', [
                'firebaseId' => $firebaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $firebaseId): JsonResponse
    {
        $event = Event::where('firebaseId', $firebaseId)->first();
        if ($event) {
            $event->delete();
        }
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}

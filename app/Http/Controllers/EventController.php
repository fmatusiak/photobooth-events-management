<?php

namespace App\Http\Controllers;

use App\Exceptions\EventsOverlapException;
use App\Repositories\EmailRepository;
use App\Repositories\EventRepository;
use App\Repositories\PackageRepository;
use App\Services\EventService;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EventController extends Controller
{
    protected EventService $eventService;
    protected EventRepository $eventRepository;

    protected PackageRepository $packageRepository;

    public function __construct(EventService $eventService, EventRepository $eventRepository, PackageRepository $packageRepository)
    {
        $this->eventService = $eventService;
        $this->eventRepository = $eventRepository;
        $this->packageRepository = $packageRepository;
    }

    public function storeEvent(Request $request): View|Application|Factory|\Illuminate\Contracts\Foundation\Application|RedirectResponse
    {
        try {
            $event = $this->eventService->createEvent($request->all());

            return redirect()->route('events.show', ['eventId' => $event->id])->with('status', __('messages.created'));
        } catch (EventsOverlapException) {
            return back()->withInput()->with('error', __('messages.events_overlap'));
        } catch (Exception $e) {
            Log::error(__('messages.error_create'), [
                'error_message' => $e->getMessage(),
                'error' => $e,
            ]);

            return back()->withInput()->with([
                'error' => __('messages.error_create'),
                'error_message' => $e->getMessage()
            ]);
        }

    }

    public function createEvent(): View
    {
        $packages = $this->packageRepository->all();

        return view('events.create', ['packages' => $packages]);
    }

    public function updateEvent(int $eventId, Request $request): View|Application|Factory|\Illuminate\Contracts\Foundation\Application|RedirectResponse
    {
        try {
            $this->eventService->updateEvent($eventId, $request->all());

            return redirect()->route('events.edit', ['eventId' => $eventId])->with('status', __('messages.updated'));
        } catch (ModelNotFoundException) {
            return back()->withInput()->with('error', __('messages.not_found'));
        } catch (EventsOverlapException) {
            return back()->withInput()->with('error', __('messages.events_overlap'));
        } catch (Exception $e) {
            Log::error(__('messages.error_update'), [
                'error_message' => $e->getMessage(),
                'error' => $e,
            ]);

            return back()->withInput()->with([
                'error', __('messages.error_update'),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    public function getEvent(int $eventId): View|Application|Factory|\Illuminate\Contracts\Foundation\Application|RedirectResponse
    {
        try {
            $event = $this->eventRepository->get($eventId);
            $packages = $this->packageRepository->all();

            return view('events.edit', ['event' => $event, 'packages' => $packages]);
        } catch (ModelNotFoundException) {
            return back()->with('error', __('messages.not_found'));
        } catch (Exception $e) {
            Log::error(__('messages.error_retrieve'), [
                'error_message' => $e->getMessage(),
                'error' => $e,
            ]);

            return back()->with([
                'error', __('messages.error_retrieve'),
                'error_message' => $e->getMessage()
            ]);
        }
    }

    public function showEvent(int $eventId, EmailRepository $emailRepository): Factory|Application|\Illuminate\Contracts\View\View|RedirectResponse|\Illuminate\Contracts\Foundation\Application
    {
        try {
            $event = $this->eventRepository->get($eventId);
            $emails = $emailRepository->paginate(['event_ids' => $eventId]);

            return view('events.show', ['event' => $event, 'emails' => $emails]);
        } catch (ModelNotFoundException) {
            return back()->with('error', __('messages.not_found'));
        } catch (Exception $e) {
            Log::error(__('messages.error_retrieve'), [
                'error_message' => $e->getMessage(),
                'error' => $e,
            ]);

            return back()->with('error', __('messages.error_retrieve'));
        }
    }

    public function paginate(Request $request): View
    {
        try {
            $filters = $request->input('filters', []);
            $columns = $request->input('columns', ['*']);
            $perPage = $request->input('per_page', 15);

            $paginator = $this->eventRepository->paginate($filters, $perPage, $columns);

            return view('events.index', ['paginator' => $paginator]);
        } catch (Exception $e) {
            Log::error(__('messages.error_pagination'), [
                'error_message' => $e->getMessage(),
                'error' => $e,
            ]);

            return view('events.index', ['error' => __('messages.error_pagination')]);
        }
    }

    public function deleteEvent(int $eventId): JsonResponse
    {
        try {
            $this->eventService->deleteEvent($eventId);

            return response()->json(['status' => __('messages.deleted')]);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => __('messages.not_found')], 404);
        } catch (Exception $e) {
            Log::error(__('messages.error_delete'), [
                'error_message' => $e->getMessage(),
                'error' => $e,
            ]);

            return response()->json([
                'error' => __('messages.error_delete'),
                'error_message' => $e->getMessage()
            ], 500);
        }
    }

}

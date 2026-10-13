<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Avis clients.
 *
 * Deux règles : on ne peut donner son avis que sur une location terminée, et une
 * seule fois par réservation. Le nom affiché vient de la réservation, pas du
 * formulaire — sinon n'importe qui pourrait se faire passer pour un client.
 */
class ReviewController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => [
                'required',
                Rule::exists('bookings', 'id')->where('user_id', $request->user()->id),
            ],
            'rating' => ['required', 'integer', 'between:1,5'],
            'condition_rating' => ['nullable', 'integer', 'between:1,5'],
            'service_rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = Booking::query()->findOrFail($data['booking_id']);

        if ($booking->status !== BookingStatus::Terminee) {
            return back()->with('error', __('review.not_finished'));
        }

        if ($booking->review !== null) {
            return back()->with('error', __('review.already_done'));
        }

        Review::query()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'vehicle_id' => $booking->vehicle_id,
            'author_name' => $request->user()->displayName(),
            'locale' => $request->user()->preferred_locale,
            'status' => ReviewStatus::EnAttente,
        ]);

        return back()->with('status', __('review.thanks_pending'));
    }
}

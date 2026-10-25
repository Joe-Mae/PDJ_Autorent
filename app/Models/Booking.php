<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'reference', 'user_id', 'vehicle_id', 'category_id', 'driver_id',
    'starts_at', 'ends_at', 'days', 'pickup_hour', 'return_hour',
    'with_driver', 'driver_blocks', 'driver_hours', 'driver_daily_price',
    'daily_price', 'price_rental', 'price_driver', 'price_total',
    'deposit_amount', 'currency', 'holiday_days',
    'status', 'status_reason',
    'document_verification_status', 'identity_original_verified', 'identity_name_matches',
    'customer_age_eligible', 'driver_license_original_verified', 'driver_license_category_authorized',
    'driver_license_category', 'driver_license_issued_at', 'driver_license_expires_at',
    'document_verification_note', 'documents_verified_at', 'documents_verified_by',
    'driver_license_copy_consent_at',
    'confirmed_at', 'cancelled_at', 'cancelled_by', 'started_at', 'ended_at',
    'mileage_at_pickup', 'mileage_at_return', 'fuel_level_pickup', 'fuel_level_return',
    'pickup_notes', 'return_notes',
    'phone_verified_at', 'reminder_sent_at', 'client_confirmed_presence_at',
    'grace_expires_at', 'public_token',
    'customer_name', 'customer_phone', 'customer_email', 'customer_comment',
    'guest_access_token_hash',
])]
class Booking extends Model implements HasMedia
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Photos de l'état des lieux, prises au retrait ou au retour. Elles
     * servent de preuve : quatre photos obligatoires dès qu'un dommage est
     * constaté, aucune sinon.
     */
    public const MEDIA_CONDITION_REPORT = 'etat_des_lieux';

    /** Signature manuscrite du contrat, saisie sur la tablette. */
    public const MEDIA_SIGNATURE = 'signature';

    protected $hidden = ['guest_access_token_hash'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_CONDITION_REPORT)
            ->useDisk('documents');

        $this->addMediaCollection(self::MEDIA_SIGNATURE)
            ->useDisk('documents')
            ->singleFile();
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'client_confirmed_presence_at' => 'datetime',
            'grace_expires_at' => 'datetime',
            'with_driver' => 'boolean',
            'status' => BookingStatus::class,
            'document_verification_status' => DocumentVerificationStatus::class,
            'identity_original_verified' => 'boolean',
            'identity_name_matches' => 'boolean',
            'customer_age_eligible' => 'boolean',
            'driver_license_original_verified' => 'boolean',
            'driver_license_category_authorized' => 'boolean',
            'driver_license_issued_at' => 'date',
            'driver_license_expires_at' => 'date',
            'documents_verified_at' => 'datetime',
            'driver_license_copy_consent_at' => 'datetime',
            'days' => 'integer',
            'driver_daily_price' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentsVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'documents_verified_by');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /** Réservations qui immobilisent encore le véhicule dans le planning. */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereIn('status', ['en_attente', 'confirmee', 'en_cours']);
    }

    /** Réservations à venir ou en cours, celles qui comptent pour le quota client. */
    #[Scope]
    protected function activeBookings(Builder $query): Builder
    {
        return $query->active();
    }

    #[Scope]
    protected function forPeriod(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->where('starts_at', '<', $to)->where('ends_at', '>', $from);
    }

    #[Scope]
    protected function today(Builder $query): Builder
    {
        return $query->whereDate('starts_at', today());
    }

    public function isBlocking(): bool
    {
        return $this->status->isBlocking();
    }

    /**
     * Réservation en cours de construction dans le tunnel : le prix est figé,
     * mais aucun document n'est encore exigé et aucun message n'est parti.
     */
    public function isNew(): bool
    {
        return $this->user_id === null && $this->phone_verified_at === null;
    }

    public function hasPhoneVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function hasVerifiedIdentity(): bool
    {
        return $this->identity_original_verified
            && $this->identity_name_matches
            && $this->customer_age_eligible;
    }

    /**
     * La photo du permis, prise en agence au moment de la remise des clés.
     * Sans elle, la location ne peut pas démarrer : c'est la preuve que le
     * permis a bien été contrôlé par la caissière.
     */
    public function hasDriverLicenseCopy(): bool
    {
        return $this->driver_license_copy_consent_at !== null
            && $this->documents()
                ->where('type', DocumentType::DriverLicense->value)
                ->exists();
    }

    public function isReadyForHandover(): bool
    {
        if (! $this->hasVerifiedIdentity()) {
            return false;
        }

        if ($this->with_driver) {
            return $this->document_verification_status === DocumentVerificationStatus::NonRequis;
        }

        return $this->document_verification_status === DocumentVerificationStatus::Conforme
            && $this->hasDriverLicenseCopy();
    }

    public function isOverdue(): bool
    {
        return $this->status === BookingStatus::EnCours
            && $this->ends_at->isPast();
    }

    /** Montant total à avoir sur soi le jour du retrait. */
    public function amountToCarry(): int
    {
        return (int) $this->price_total + (int) $this->deposit_amount;
    }

    /**
     * Une caution est-elle attendue pour cette réservation ?
     *
     * Le montant est figé à la création : l'agence peut changer d'avis pour un
     * véhicule sans toucher aux réservations déjà prises.
     */
    public function requiresDeposit(): bool
    {
        return (int) $this->deposit_amount > 0;
    }

    /** Caution effectivement versée, déduction faite de ce qui a été prélevé au retour. */
    public function depositPaid(): int
    {
        return (int) $this->payments()->where('kind', 'caution')->sum('amount');
    }

    public function depositDeducted(): int
    {
        return (int) $this->payments()->where('kind', 'caution_restitution')->sum('deducted_amount');
    }

    public function depositRefundable(): int
    {
        return max(0, $this->depositPaid() - $this->depositDeducted());
    }

    public function rentalPaid(): int
    {
        return (int) $this->payments()->where('kind', 'location')->sum('amount');
    }

    public function isFullyPaid(): bool
    {
        return $this->rentalPaid() >= (int) $this->price_total;
    }
}

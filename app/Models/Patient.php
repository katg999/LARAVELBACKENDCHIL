<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', // Unique patient identifier
        'name',
        'gender',
        'birth_date',
        'contact_number',
        'parent_contact', // For students
        'grade', // For students
        'medical_history', // Medical history/notes
        'school_id', // Nullable - for school patients
        'health_facility_id', // Nullable - for health facility patients
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($patient) {
            if (empty($patient->patient_id)) {
                $patient->patient_id = static::generateUniquePatientId();
            }
        });
    }

    /**
     * Generate a unique patient ID
     */
    public static function generateUniquePatientId(): string
    {
        do {
            $id = 'P' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('patient_id', $id)->exists());

        return $id;
    }

    /**
     * Find or create a patient by identification criteria
     */
    public static function findOrCreate(array $identificationAttributes, array $additionalAttributes = []): self
    {
        // Merge all attributes
        $allAttributes = array_merge($identificationAttributes, $additionalAttributes);

        // Try to find existing patient by various criteria
        $query = static::query();

        // Search by patient_id if provided
        if (!empty($identificationAttributes['patient_id'])) {
            $existing = $query->where('patient_id', $identificationAttributes['patient_id'])->first();
            if ($existing) {
                // Update associations if needed
                $existing->updateAssociations($allAttributes);
                return $existing;
            }
        }

        // Search by name, birth_date, gender, and contact
        if (!empty($identificationAttributes['name']) && !empty($identificationAttributes['birth_date']) && !empty($identificationAttributes['gender'])) {
            $existing = $query->where('name', $identificationAttributes['name'])
                              ->whereDate('birth_date', $identificationAttributes['birth_date'])
                              ->where('gender', $identificationAttributes['gender'])
                              ->when(!empty($identificationAttributes['contact_number']), function($q) use ($identificationAttributes) {
                                  return $q->where('contact_number', $identificationAttributes['contact_number']);
                              })
                              ->when(!empty($identificationAttributes['parent_contact']), function($q) use ($identificationAttributes) {
                                  return $q->orWhere('parent_contact', $identificationAttributes['parent_contact']);
                              })
                              ->first();

            if ($existing) {
                // Update associations if needed
                $existing->updateAssociations($allAttributes);
                return $existing;
            }
        }

        // Create new patient
        return static::create($allAttributes);
    }

    /**
     * Update associations for existing patient
     */
    protected function updateAssociations(array $attributes): void
    {
        $updates = [];

        if (!empty($attributes['school_id'])) {
            $updates['school_id'] = $attributes['school_id'];
        }

        if (!empty($attributes['health_facility_id'])) {
            // If patient doesn't have a primary health facility, set it
            if (!$this->health_facility_id) {
                $updates['health_facility_id'] = $attributes['health_facility_id'];
            }
            // Always attach to the many-to-many relationship (will be ignored if already attached)
            $this->healthFacilities()->syncWithoutDetaching([$attributes['health_facility_id']]);
        }

        // Update other fields if they're empty
        $fillableFields = ['gender', 'contact_number', 'parent_contact', 'grade', 'medical_history'];
        foreach ($fillableFields as $field) {
            if (!empty($attributes[$field]) && empty($this->$field)) {
                $updates[$field] = $attributes[$field];
            }
        }

        if (!empty($updates)) {
            parent::update($updates);
        }
    }

    /**
     * Override update method to handle health facility associations
     */
    public function update(array $attributes = [], array $options = [])
    {
        // Handle health facility associations
        if (!empty($attributes['health_facility_id'])) {
            // Always attach to the many-to-many relationship
            $this->healthFacilities()->syncWithoutDetaching([$attributes['health_facility_id']]);
        }

        return parent::update($attributes, $options);
    }

    /**
     * Relationships
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function healthFacility(): BelongsTo
    {
        return $this->belongsTo(HealthFacility::class);
    }

    public function healthFacilities(): BelongsToMany
    {
        return $this->belongsToMany(HealthFacility::class)->withTimestamps();
    }

    public function policies(): HasMany
    {
        return $this->hasMany(MemberPolicy::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function maternalDocuments(): HasMany
    {
        return $this->hasMany(MaternalDocument::class);
    }

    public function labTests(): HasMany
    {
        return $this->hasMany(LabTest::class);
    }

    public function medicalHistories(): HasMany
    {
        return $this->hasMany(MedicalHistory::class);
    }

    /**
     * Scopes
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForHealthFacility($query, $facilityId)
    {
        return $query->where(function ($q) use ($facilityId) {
            $q->where('health_facility_id', $facilityId)
              ->orWhereHas('healthFacilities', function ($q) use ($facilityId) {
                  $q->where('health_facility_id', $facilityId);
              });
        });
    }

    public function scopeStudents($query)
    {
        return $query->whereNotNull('school_id');
    }

    public function scopePatients($query)
    {
        return $query->whereNotNull('health_facility_id')
                    ->orWhereHas('healthFacilities');
    }

    /**
     * Accessors
     */
    public function getAgeAttribute()
    {
        return $this->birth_date ? \Carbon\Carbon::parse($this->birth_date)->age : null;
    }

    public function getIsStudentAttribute()
    {
        return !is_null($this->school_id);
    }

    public function getIsHealthFacilityPatientAttribute()
    {
        return !is_null($this->health_facility_id) || $this->healthFacilities()->exists();
    }

    public function getInstitutionAttribute()
    {
        return $this->school ?? $this->healthFacility;
    }
}
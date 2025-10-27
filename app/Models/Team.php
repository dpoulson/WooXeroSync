<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use Illuminate\Database\Eloquent\Casts\AsEncryptedString; 

class Team extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
        'wc_payment_account_map',
        'woocommerce_url',
        'woocommerce_consumer_key',
        'woocommerce_consumer_secret'
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'xero_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Get the Xero connection details for the team.
     */
    public function xeroConnection()
    {
        return $this->hasOne(XeroConnection::class);
    }

    /**
     * Get the WooCommerce connection details for the team.
     */
    public function woocommerceConnection()
    {
        return $this->hasOne(WoocommerceConnection::class);
    }
}

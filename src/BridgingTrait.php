<?php

namespace App\Traits;

use App\Models\Bridging;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

trait BridgingTrait
{
    /**
     * Get the bridging associated with the CompanyTrait
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bridging(): HasOne
    {
        return $this->hasOne(Bridging::class, 'id');
    }

    public function setBridging($request)
    {
        $bridging = $request['bridging'] ?? [];
        return $this->when(!empty($bridging), function () use ($bridging) {
            return $this->bridging()->save(
                new Bridging([
                    'id' => $this->id,
                    'model' => (__CLASS__),
                    'vendor_id' => $bridging['vendor_id'],
                    'vendor_primary_id' => $bridging['vendor_primary_id'],
                ])
            );
        });
    }
}

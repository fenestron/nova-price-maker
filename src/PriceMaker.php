<?php

namespace Fenestron\PriceMaker;

use Laravel\Nova\Fields\Field;

class PriceMaker extends Field
{
    public $component = 'price-maker';

    public function init($service)
    {
        return $this->withMeta([
            'service_id' => $service->id,
            'min_clients' => $service->min_clients,
            'max_clients' => $service->max_clients,
            'asset_count' => $service->asset_count, //
            'payment_type' => $service->client_type, //
            'price_type' => $service->price_type,
            'fix_price' => $service->fix_price,
            'workzones_type' => $service->time_type, //
            'durations' => $service->durations,
            'options' => $service->opt, // options
            'workzones' => $service->workzones,
            'costs' => $service->costs,
        ]);
    }
}

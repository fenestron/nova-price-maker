<?php

namespace Fenestron\PriceMaker;

use Laravel\Nova\Fields\Field;

class PriceMaker extends Field
{
    public $component = 'price-maker';

    public function init($service)
    {
        $service_data = [
            'service_id' => $service->id,
            'min_clients' => $service->min_clients,
            'max_clients' => $service->max_clients,
            'asset_count' => $service->asset_count,
            'client_type' => $service->customer_type,
            'price_type' => $service->price_type,
            'fix_price' => $service->fix_price,
            'time_type' => $service->type == 'club' ? 'auto' : $service->type,
            'durations' => $service->durations,
            'options' => $service->opt, // options
            'workzones' => $service->workzones,
            'costs' => $service->costs,
        ];

        return $this->withMeta($service_data);
    }
}

<?php

return [
    'ckan_base_url' => env('VEGVESEN_CKAN_URL', 'https://dataut.vegvesen.no/api/3/action'),
    'default_query' => env('VEGVESEN_DEFAULT_QUERY', 'Narvik'),
    'resource_formats' => array_filter(array_map('trim', explode(',', env('VEGVESEN_RESOURCE_FORMATS', 'JSON,WFS,WMS,XML')))),
];

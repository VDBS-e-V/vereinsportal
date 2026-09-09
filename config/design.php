<?php

return [
    /*
     * The design workbench is always available in local and testing
     * environments. Set DESIGN_SYSTEM_ENABLED=true explicitly if it
     * should also be available in another environment.
     */
    'enabled' => (bool) env(
        'DESIGN_SYSTEM_ENABLED',
        false,
    ),
];

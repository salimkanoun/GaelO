<?php

namespace App\GaelO\Interfaces\Adapters;

interface FrameworkInterface {

    /**
     * Instanciate class with Depedency injection
     */
    public static function make(string $className);

    /**
     * Config Available Keys are defined in SettingsConstants
     */
    public static function getConfig(string $key);

    /**
     * Get storage path in the project
     */
    public static function getStoragePath() : string ;

}
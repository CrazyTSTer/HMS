<?php

class CommonSettings
{
    /** @var  Config */
    public static $cfg;

    public function __construct($cfgName)
    {
        self::$cfg = Config::getConfig($cfgName);
    }

    public static function actionGetMetersSettings()
    {
        $ret = self::$cfg->get();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }

    public static function actionSaveMetersSettings()
    {
        $dataToSave = json_decode(Vars::get('dataToSave', null), true);
        self::$cfg->set(null, $dataToSave);
        self::$cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Saved');
    }

    public static function actionEraseMetersSettings()
    {
        self::$cfg->drop();
        self::$cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Erased');
    }
}

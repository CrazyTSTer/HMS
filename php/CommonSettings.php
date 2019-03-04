<?php

class CommonSettings
{
    /** @var  Config */
    public $cfg;

    public function __construct($cfgName)
    {
        $this->cfg = Config::getConfig($cfgName);
    }

    public function actionGetMetersSettings()
    {
        $ret = $this->cfg->get();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }

    public function actionSaveMetersSettings()
    {
        $dataToSave = json_decode(Vars::get('dataToSave', null), true);
        $this->cfg->set(null, $dataToSave);
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Saved');
    }

    public function actionEraseMetersSettings()
    {
        $this->cfg->drop();
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Erased');
    }
}

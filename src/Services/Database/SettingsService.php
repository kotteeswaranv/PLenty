<?php

namespace Payreto\Services\Database;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\System\Contracts\WebstoreRepositoryContract;
use Plenty\Plugin\Log\Loggable;
use Payreto\Models\Database\Settings;
use Payreto\Helper\PaymentHelper;

/**
* Class SettingsService
* @package Payreto\Services\Database
*/
class SettingsService extends DatabaseBaseService
{
    use Loggable;

    /**
     * @var tableName
     */
    protected $tableName = 'settings';

    /**
     * SettingsService constructor.
     * @param DataBase $dataBase
     */
    public function __construct(DataBase $dataBase)
    {
        parent::__construct($dataBase);
    }

    /**
     * load the settings by parameters given
     *
     * @param string $webstore
     * @param string $mode
     * @return array|null
     */
    public function loadSetting($webstore, $mode)
    {
        $setting = $this->getValues(Settings::class, ['name', 'webstore'], [$mode, $webstore], ['=','=']);
        if (is_array($setting) && $setting[0] instanceof Settings)
        {
            return $setting[0]->value;
        }
        return null;
    }

    /**
     * load the settings
     *
     * @param string $settingType
     * @return array
     */
    public function loadSettings($settingType)
    {
        $settings = array();
        $results = $this->getValues(Settings::class);
        if (is_array($results))
        {
            foreach ($results as $item)
            {
                if ($item instanceof Settings && $item->name == $settingType)
                {
                    $settings[] = ['PID_'.$item->webstore => $item->value];
                }
            }
        }
        return $settings;
    }

    /**
     * save the settings
     *
     * @param string $mode
     * @param array $settings
     * @return bool
     */
    public function saveSettings($mode, $settings)
    {
        if ($settings)
        {
            foreach ($settings as $setting)
            {
                foreach ($setting as $store => $values)
                {
                    $id = 0;
                    $store = (int)str_replace('PID_', '', $store);

                    if ($store > 0)
                    {
                        $existValue = $this->getValues(Settings::class, ['name', 'webstore'], [$mode, $store], ['=','=']);
                        if (isset($existValue) && is_array($existValue))
                        {
                            if ($existValue[0] instanceof Settings)
                            {
                                $id = $existValue[0]->id;
                            }
                        }

                        /** @var Settings $settingModel */
                        $settingModel = pluginApp(Settings::class);
                        if ($id > 0)
                        {
                            $settingModel->id = $id;
                        }
                        $settingModel->webstore = $store;
                        $settingModel->name = $mode;
                        $settingModel->value = $values;
                        $settingModel->updatedAt = date('Y-m-d H:i:s');

                        if ($settingModel instanceof Settings)
                        {
                            $this->setValue($settingModel);
                        }
                    }
                }
            }
            return 1;
        }
    }

    /**
     * Get available clients of the system
     *
     * @return array
     */
    public function getClients()
    {
        $webstoreRepository = pluginApp(WebstoreRepositoryContract::class);

        $clients = array();

        $result = $webstoreRepository->loadAll();

        foreach ($result as $record)
        {
            if ($record->storeIdentifier > 0)
            {
                $clients[] = $record->storeIdentifier;
            }
        }

        return $clients;
    }

    /**
     * set initial settings payment method name for each plentyId
     *
     */
    // public function setInitialSettings()
    // {
    //     $clients = $this->getClients();

    //     foreach ($clients as $plentyId)
    //     {
    //         $settings = array();
    //         $settings[] = array(
    //             'PID_'.$plentyId => array(
    //                 'language' => $value
    //             )
    //         );
    //         $this->saveSettings('payreto_settings', $settings);
    //     }
    // }

    /**
     * get Payreto configuration by plentyId and settingType
     *
     * @param string $plentyId
     * @param string $settingType
     * @return array
     */
    public function getConfiguration($plentyId, $settingType)
    {
        $paymentHelper = pluginApp(PaymentHelper::class);
        $url = $paymentHelper->getDomain() . '/rest/payment/payreto/setting/' . $plentyId . '/' . $settingType;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $_COOKIE['accessToken']));

        $response = curl_exec($curl);
        if (curl_errno($curl))
        {
            $this->getLogger(__METHOD__)->error('Payreto:error', curl_error($curl));
            throw new \Exception(curl_error($curl));
        }
        curl_close($curl);

        return json_decode($response, true);
    }

    /**
     * save Payreto configuration to database
     *
     * @param string $parameters
     * @return boolean
     */
    public function saveConfiguration($parameters)
    {
        $paymentHelper = pluginApp(PaymentHelper::class);
        $postFields = json_encode($parameters);

        $url = $paymentHelper->getDomain() . '/rest/payment/payreto/settings/';
        $header = array(
            'Content-type: application/json',
            'Authorization: Bearer ' . $_COOKIE['accessToken']
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);

        $response = curl_exec($curl);
        if (curl_errno($curl))
        {
            $this->getLogger(__METHOD__)->error('Payreto:error', curl_error($curl));
            return 0;
        }
        curl_close($curl);

        return $response;
    }
}

<?php

namespace Grav\Plugin\TecartSearch\Classes\BlueprintHelper;

use Grav\Common\Grav;
use Grav\Common\Data\Data;

/**
 * Tecart Cookie Manager Plugin Cookie Manager Class
 *
 */
class BlueprintHelper extends Data {

    /**
     * function to call with parameters from blueprints to dynamically fetch the  option list
     * do this by using data-*@: notation as the key, where * is the field name you want to fill with the result of the function call
     *
     * data-options@: 'Grav\Plugin\TecartSearch\Classes\BlueprintHelper\BlueprintHelper::getAdminPermissionsForBlueprintOptions'
     *
     * @return array
     */
    public static function getAdminPermissionsForBlueprintOptions(): array
    {
        $grav = Grav::instance();
        $return = array();
        if (!isset($grav['admin'])) {
            return $return;
        }
        if (\method_exists($grav['admin'], 'getPermissions')) {
            $permissions = $grav['admin']->getPermissions();
        } elseif (\method_exists($grav['permissions'], 'getInstances')) {
            $permissions =$grav['permissions']->getInstances();
        }
        if (is_array($permissions) && !empty($permissions)) {
            foreach (array_keys($permissions) as $permission) {
                $return[] = [
                    'text' => $permission,
                    'value' => $permission
                ];
            }
        }
        return $return;
    }
}

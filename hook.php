<?php

/**
 * -------------------------------------------------------------------------
 * restrict_reservations plugin for GLPI
 * Copyright (C) 2023 by the restrict_reservations Development Team.
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * --------------------------------------------------------------------------
 */

function plugin_restrict_reservations_check_reservation(CommonDBTM $item)
{
    global $CFG_GLPI;
    // Convert date strings to DateTime objects
    $beginDate = new DateTime($item->input["begin"]);
    $endDate = new DateTime($item->input["end"]);

    // Calculate the difference in days
    $interval = $beginDate->diff($endDate);
    $daysDifference = $interval->days;

    // Check if the difference is less than or equal to 30 days
    if ($daysDifference > 30) {
        $item->input = false;
        Html::displayErrorAndDie("We have limited the maximum reservation length to 30 Days to ensure that assets are actively used. Please contact the GLPI administrators if you have any feedback.");
    }

    // Get the reservation item ID
    $reservationitems_id = $item->input["reservationitems_id"];

    // Get the ID of the currently logged-in user
    $currentUserID = Session::getLoginUserID();

    // Get group & ancestor groups of asset
    $reservationItem = new ReservationItem();
    $reservationItem->getFromDB($reservationitems_id);
    $asset = new $reservationItem->fields["itemtype"]();
    $asset->getFromDB($reservationItem->fields["items_id"]);
    $assetGroup = $asset->fields['groups_id'];
    $ancestor_groups = getAncestorsOf("glpi_groups", $assetGroup);

    $canReserve = false;
    if (Group_User::isUserInGroup($currentUserID, $assetGroup)) {
        // Check if the current user is in the asset's group
        $canReserve = true;
    } elseif (!empty($ancestor_groups)) {
        // Check if the current user is in any of the ancestor groups
        foreach ($ancestor_groups as $group) {
            if (Group_User::isUserInGroup($currentUserID, $group)) {
                $canReserve = true;
                break;
            }
        }
    }

    if (!$canReserve) {
        // If the user is not allowed to reserve the asset, display an error message
        $item->input = false;
        Html::displayErrorAndDie("You do not belong to the appropriate group to reserve this machine. You need to either be in the same group as the requested asset, or in an ancestor group of the asset. You can check your groups here: https://{$_SERVER['HTTP_HOST']}/front/user.form.php?id=$currentUserID&forcetab=Group_User$1. If you are in the correct groups, but would still like to reserve this asset, please ask someone in the relevant group to reserve it on your behalf. If you are not in the correct group, please contact your manager or the GLPI admins.");
    }

    return $item;
}
/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_restrict_reservations_install()
{
    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_restrict_reservations_uninstall()
{
    return true;
}

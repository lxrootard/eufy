<?php

/* This file is part of Plugin eufy for jeedom.
 *
 * Plugin eufy for jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Plugin eufy for jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Plugin eufy for jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__  . '/../core/class/eufyUtils.php';

function eufy_install() {
	eufyUtils::initConfig();
}

function eufy_update() {
	eufyUtils::initConfig();
}

function eufy_remove() {

}

?>

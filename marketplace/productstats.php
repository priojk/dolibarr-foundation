<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       marketplace/productstats.php
 *	\ingroup    marketplace
 *	\brief      Products stats home page of marketplace top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


// Load translation files required by the page
$langs->loadLangs(array('commande', 'propal', 'bills', 'other', 'products','marketplace@marketplace'));

$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$type = GETPOST('type', 'intcomma');
$mode = GETPOST('mode', 'alpha') ? GETPOST('mode', 'alpha') : '';
$mode = 'facture';

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
if (!$sortfield) {
	$sortfield = "c";
}
if (!$sortorder) {
	$sortorder = "DESC";
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

restrictedArea($user, 'produit|service', 0, 'product&product', '', '');



/*
 * View
 */

 $form = new Form($db);
 $tmpproduct = new Product($db);
 
 $title = $langs->trans("Statistics");
 
 
 llxHeader("", $langs->trans("MarketplaceArea"), '', '', 0, 0, '', '', '', 'mod-marketplace page-index');

 print load_fiche_titre($langs->trans("MarketplaceArea"), '', 'fa-store');
 
 
 $param = '';
 $title = $langs->trans("ListProductServiceByPopularity");
 if ((string) $type == '1') {
	 $title = $langs->trans("ListServiceByPopularity");
 }
 if ((string) $type == '0') {
	 $title = $langs->trans("ListProductByPopularity");
 }
 
 if ($type != '') {
	 $param .= '&type='.urlencode($type);
 }
 if ($mode != '') {
	 $param .= '&mode='.urlencode($mode);
 }
 
 
 $h = 0;
 $head = array();
 
 $head[$h][0] = DOL_URL_ROOT.'/custom/marketplace/marketplaceindex.php';
 $head[$h][1] = $langs->trans("invoicesStatsMarketplace");
 $head[$h][2] = 'invoicesStatsMarketplace';
 $h++;
 
 $head[$h][0] = DOL_URL_ROOT.'/custom/marketplace/customerstats.php';
 $head[$h][1] = $langs->trans("customersStatsMarketplace");
 $head[$h][2] = 'customersStatsMarketplace';
 $h++;
 
 $head[$h][0] = DOL_URL_ROOT.'/custom/marketplace/productstats.php';
 $head[$h][1] = $langs->trans("productsStatsMarketplace");
 $head[$h][2] = 'productsStatsMarketplace';
 $h++;
 
 
 print dol_get_fiche_head($head, 'productsStatsMarketplace', '', -1);
 
 
 // Array of lines to show
 $infoprod = array();
 
 
 // Add lines for object
 $sql = "SELECT p.rowid, p.label, p.ref, p.fk_product_type as type, p.tobuy, p.tosell, p.tobatch, p.barcode, SUM(pd.qty) as c";
 $textforqty = 'Qty';
 if ($mode == 'facture') {
	 $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as pd";
 } elseif ($mode == 'commande') {
	 $textforqty = 'NbOfQtyInOrders';
	 $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as pd";
 } elseif ($mode == 'propal') {
	 $textforqty = 'NbOfQtyInProposals';
	 $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pd";
 }
 $sql .= ", ".MAIN_DB_PREFIX."product as p";
 $sql .= ", ".MAIN_DB_PREFIX."categorie_product as cp";
 $sql .= ' WHERE p.entity IN ('.getEntity('product').') AND p.rowid = cp.fk_product AND cp.fk_categorie = '.((int) getDolGlobalString('MARKETPLACE_ROOT_CATEGORY_ID'));
 $sql .= " AND p.rowid = pd.fk_product";
 if ($type !== '') {
	 $sql .= " AND fk_product_type = ".((int) $type);
 }
 $sql .= " GROUP BY p.rowid, p.label, p.ref, p.fk_product_type, p.tobuy, p.tosell, p.tobatch, p.barcode";
 
 $num = 0;
 $totalnboflines = 0;
 
 if (!empty($mode) && $mode != '-1') {
	 $result = $db->query($sql);
	 if ($result) {
		 $totalnboflines = $db->num_rows($result);
	 }
 
	 $sql .= $db->order($sortfield, $sortorder);
	 $sql .= $db->plimit($limit + 1, $offset);
 
	 $resql = $db->query($sql);
	 if ($resql) {
		 $num = $db->num_rows($resql);
		 $i = 0;
 
		 while ($i < $num) {
			 $objp = $db->fetch_object($resql);
 
			 $infoprod[$objp->rowid] = array('type' => $objp->type, 'ref' => $objp->ref, 'label' => $objp->label, 'tobuy' => $objp->tobuy, 'tosell' => $objp->tobuy, 'tobatch' => $objp->tobatch, 'barcode' => $objp->barcode);
			 $infoprod[$objp->rowid]['nbline'] = $objp->c;
 
			 $i++;
		 }
		 $db->free($resql);
	 } else {
		 dol_print_error($db);
	 }
 }
 //var_dump($infoprod);
 
 
 /*$arrayofmode = array(
	 'propal' => 'Proposals',
	 'commande' => 'Orders',
	 'facture' => 'Facture'
	 );
 $title .= ' '.$form->selectarray('mode', $arrayofmode, $mode, 1, 0, 0, '', 1);
 $title .= ' <input type="submit" class="button small" name="refresh" value="'.$langs->trans("Refresh").'">';*/
 
 
 print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
 print '<input type="hidden" name="token" value="'.newToken().'">';
 print '<input type="hidden" name="mode" value="'.$mode.'">';
 print '<input type="hidden" name="type" value="'.$type.'">';
 print '<input type="hidden" name="action" value="add">';
 if ($backtopage) {
	 print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
 }
 if ($backtopageforcancel) {
	 print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
 }
 
 
 print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $totalnboflines, '', 0, '', '', -1, 0, 0, 1);
 
 print '<table class="noborder centpercent">';
 
 print '<tr class="liste_titre">';
 print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
 print_liste_field_titre('Type', $_SERVER["PHP_SELF"], 'p.fk_product_type', '', $param, '', $sortfield, $sortorder);
 print_liste_field_titre('Label', $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
 print_liste_field_titre($textforqty, $_SERVER["PHP_SELF"], 'c', '', $param, '', $sortfield, $sortorder, 'right ');
 print "</tr>\n";
 
 if ($mode && $mode != '-1') {
	 foreach ($infoprod as $prodid => $vals) {
		 // Multilangs
		 if (getDolGlobalInt('MAIN_MULTILANGS')) { // si l'option est active
			 $sql = "SELECT label";
			 $sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
			 $sql .= " WHERE fk_product = ".((int) $prodid);
			 $sql .= " AND lang = '".$db->escape($langs->getDefaultLang())."'";
			 $sql .= " LIMIT 1";
 
			 $resultp = $db->query($sql);
			 if ($resultp) {
				 $objtp = $db->fetch_object($resultp);
				 if (!empty($objtp->label)) {
					 $vals['label'] = $objtp->label;
				 }
			 }
		 }
 
		 $tmpproduct->id = $prodid;
		 $tmpproduct->ref = $vals['ref'];
		 $tmpproduct->label = $vals['label'];
		 $tmpproduct->type = $vals['type'];
		 $tmpproduct->status = $vals['tosell'];
		 $tmpproduct->status_buy = $vals['tobuy'];
		 $tmpproduct->status_batch = $vals['tobatch'];
		 $tmpproduct->barcode = $vals['barcode'];
 
		 print "<tr>";
		 print '<td>';
		 print $tmpproduct->getNomUrl(1);
		 print '</td>';
		 print '<td>';
		 $s = '';
		 if ($vals['type'] == 1) {
			 $s .= img_picto($langs->trans("Service"), 'service', 'class="paddingleftonly paddingrightonly colorgrey"');
		 } else {
			 $s .= img_picto($langs->trans("Product"), 'product', 'class="paddingleftonly paddingrightonly colorgrey"');
		 }
		 print $s;
		 print '</td>';
		 print '<td>'.dol_escape_htmltag($vals['label']).'</td>';
		 print '<td class="right">'.$vals['nbline'].'</td>';
		 print "</tr>\n";
	 }
 } else {
	 print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("SelectTheTypeOfObjectToAnalyze").'</span></td></tr>';
 }
 print "</table>";
 
 print '</form>';
 
 print dol_get_fiche_end();
 
 // End of page
 llxFooter();
 $db->close();
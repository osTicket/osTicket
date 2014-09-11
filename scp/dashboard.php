<?php
/*********************************************************************
    dashboard.php

    Staff's Dashboard - basic stats...etc.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
$nav->setTabActive('dashboard');
$ost->addExtraHeader('<meta name="tip-namespace" content="dashboard.dashboard" />',
    "$('#content').data('tipNamespace', 'dashboard.dashboard');");
require(STAFFINC_DIR.'header.inc.php');
?>

<script type="text/javascript" src="js/raphael-min.js"></script>
<script type="text/javascript" src="js/g.raphael.js"></script>
<script type="text/javascript" src="js/g.line-min.js"></script>
<script type="text/javascript" src="js/g.dot-min.js"></script>
<script type="text/javascript" src="js/bootstrap-tab.js"></script>
<script type="text/javascript" src="js/dashboard.inc.js"></script>

<link rel="stylesheet" type="text/css" href="css/bootstrap.css"/>
<link rel="stylesheet" type="text/css" href="css/dashboard.css"/>

<h2>Activité du ticket&nbsp;<i class="help-tip icon-question-sign" href="#ticket_activity"></i></h2>
<p>Sélectionnez l'heure de début et la période pour le graphe d'activité</p>
<form class="well form-inline" id="timeframe-form">
    <label>
        <i class="help-tip icon-question-sign" href="#report_timeframe"></i>&nbsp;&nbsp;Calendrier du rapport :
        <input type="text" class="dp input-medium search-query"
            name="start" placeholder="Last month"/>
    </label>
    <label>
        période&nbsp;:
        <select name="period">
            <option value="now" selected="selected">Jusqu'à aujourd'hui</option>
            <option value="+7 days">Une semaine</option>
            <option value="+14 days">Deux semaines</option>
            <option value="+1 month">Un mois</option>
            <option value="+3 months">Un trimestre</option>
        </select>
    </label>
    <button class="btn" type="submit">Rafraîchir</button>
</form>

<!-- Create a graph and fetch some data to create pretty dashboard -->
<div style="position:relative">
    <div id="line-chart-here" style="height:300px"></div>
    <div style="position:absolute;right:0;top:0" id="line-chart-legend"></div>
</div>

<hr/>
<h2>Statistiquess&nbsp;<i class="help-tip icon-question-sign" href="#statistics"></i></h2>
<p>Statistiques des tickets, organisées par département, sujet d'aide et personnel.</p>
<ul class="nav nav-tabs" id="tabular-navigation"></ul>

<div id="table-here"></div>

<?php
include(STAFFINC_DIR.'footer.inc.php');
?>

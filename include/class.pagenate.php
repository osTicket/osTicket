<?php
/*********************************************************************
    class.format.php

    Pagenation  support class

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class PageNate {

    var $start;
    var $limit;
    var $slack = 0;
    var $total;
    var $page;
    var $pages;


    function __construct($total,$page,$limit=20,$url='') {
        $this->total = intval($total);
        $this->limit = max($limit, 1 );
        $this->page  = max($page, 1 );
        $this->start = max((($page-1)*$this->limit),0);
        $this->pages = ceil( $this->total / $this->limit );

        if (($this->limit > $this->total) || ($this->page>ceil($this->total/$this->limit))) {
            $this->start = 0;
        }
        if (($this->limit-1)*$this->start > $this->total) {
            $this->start -= $this->start % $this->limit;
        }
        $this->setURL($url);
    }

    function setURL($url='',$vars='') {
        if ($url) {
            if (strpos($url, '?')===false)
                $url .= '?';
        } else {
         $url = THISPAGE.'?';
        }

        if ($vars && is_array($vars))
            $vars = Http::build_query($vars);

        $this->url = $url.$vars;
    }

    function getStart() {
        return max($this->start - $this->slack, 0);
    }

    function getStop() {
        return min($this->getStart() + $this->getLimit(), $this->total);
    }

    function getCount() {
        return $this->total;
    }

    function getLimit() {
        return $this->limit;
    }

    function setSlack($count) {
        $this->slack = $count;
    }

    function getNumPages(){
        return $this->pages;
    }

    function getPage() {
        return ceil(($this->start+1)/$this->limit);
    }

    function showing() {
        $html = '';
        $start = $this->getStart() + 1;
        $end = min($start + $this->limit + $this->slack - 1, $this->total);
        if ($end < $this->total) {
            $to= $end;
        } else {
            $to= $this->total;
        }
        $html=__('Showing')."&nbsp;";
        if ($this->total > 0) {
            $html .= sprintf(__('%1$d - %2$d of %3$d' /* Used in pagination output */),
               $start, $end, $this->total);
        }else{
            $html .= " 0 ";
        }
        return $html;
    }

    function getPageLinks($hash=false, $pjax=false) {
        $html                 = '';
        $file                =$this->url;
        $displayed_span     = 5;
        $total_pages         = ceil( ($this->total - $this->slack) / $this->limit );
        $this_page             = ceil( ($this->start+1) / $this->limit );

        $last=$this_page-1;
        $next=$this_page+1;

        $start_loop         = floor($this_page-$displayed_span);
        $stop_loop          = ceil($this_page + $displayed_span);



        $stopcredit    =($start_loop<1)?0-$start_loop:0;
        $startcredit   =($stop_loop>$total_pages)?$stop_loop-$total_pages:0;

        $start_loop =($start_loop-$startcredit>0)?$start_loop-$startcredit:1;
        $stop_loop  =($stop_loop+$stopcredit>$total_pages)?$total_pages:$stop_loop+$stopcredit;

        if($start_loop>1){
            $lastspan=($start_loop-$displayed_span>0)?$start_loop-$displayed_span:1;
            $html .= "\n<a href=\"$file&p=$lastspan\" ><strong>&laquo;</strong></a>";
        }

        for ($i=$start_loop; $i <= $stop_loop; $i++) {
            $page = ($i - 1) * $this->limit;
            $href = "{$file}&amp;p={$i}";
            if ($hash)
                $href .= '#'.$hash;
            if ($i == $this_page) {
                $html .= "\n<b>[$i]</b>";
            }
            elseif ($pjax) {
                $html .= " <a href=\"{$href}\" data-pjax-container=\"{$pjax}\"><b>$i</b></a>";
            } else {
                $html .= "\n<a href=\"{$href}\" ><b>$i</b></a>";
            }
        }
        if($stop_loop<$total_pages){
            $nextspan=($stop_loop+$displayed_span>$total_pages)?$total_pages-$displayed_span:$stop_loop+$displayed_span;
            $href = "{$file}&amp;p={$nextspan}";
            if ($hash)
                $href .= '#'.$hash;
            $html .= "\n<a href=\"{$href}\" ><strong>&raquo;</strong></a>";
        }



        return $html;
    }

    function paginate(QuerySet $qs) {
        $start = $this->getStart();
        $end = min($start + $this->getLimit() + $this->slack + ($start > 0 ? $this->slack : 0), $this->total);
        return $qs->limit($end-$start)->offset($start);
    }

}
?>

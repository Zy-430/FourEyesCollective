<?php
// lib/SimplePager.php
class SimplePager {
    public $result;
    public $count;
    public $item_count;
    public $page;
    public $page_count;
    public $limit;
    
    public function __construct($query, $params, $limit, $page = 1) {
        global $_db;
        
        $this->limit = $limit;
        $this->page = max(1, intval($page));
        $offset = ($this->page - 1) * $limit;
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as cnt FROM ($query) as sub";
        $countStm = $_db->prepare($countQuery);
        $countStm->execute($params);
        $countResult = $countStm->fetch();
        $this->item_count = $countResult->cnt;
        
        // Calculate page count
        $this->page_count = ceil($this->item_count / $limit);
        
        // Get paged results
        $pagedQuery = $query . " LIMIT $limit OFFSET $offset";
        $stm = $_db->prepare($pagedQuery);
        $stm->execute($params);
        $this->result = $stm->fetchAll();
        $this->count = count($this->result);
    }

    public function html($href = '', $attr = '') {
        if (!$this->result) return;

        // Generate pager (html)
        $prev = max($this->page - 1, 1);
        $next = min($this->page + 1, $this->page_count);

        echo "<nav class='pager' $attr>";
        echo "<a href='?page=1&$href'>First</a>";
        echo "<a href='?page=$prev&$href'>Previous</a>";

        for ($p = 1; $p <= $this->page_count; $p++) {
            $c = $p == $this->page ? 'active' : '';
            echo "<a href='?page=$p&$href' class='$c'>$p</a>";
        }

        echo "<a href='?page=$next&$href'>Next</a>";
        echo "<a href='?page=$this->page_count&$href'>Last</a>";
        echo "</nav>";
    }
}
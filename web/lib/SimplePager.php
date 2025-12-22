<?php
// lib/SimplePager.php
class SimplePager {
    public $result;
    public $count;
    public $item_count;
    public $page;
    public $page_count;
    public $limit;
        private $query_params;

    
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

        // Store current query parameters
        $this->query_params = $_GET;
        unset($this->query_params['page']); // Remove page from query params
    }

    public function html($href = '', $attr = '') {
        if (!$this->result) return;

        // Build query string from stored parameters
        $query_string = http_build_query($this->query_params);
        
        // Append additional href if provided
        if ($href) {
            if ($query_string) {
                $query_string .= '&' . $href;
            } else {
                $query_string = $href;
            }
        }

        // Generate pager (html)
        $prev = max($this->page - 1, 1);
        $next = min($this->page + 1, $this->page_count);

        echo "<nav class='pager' $attr>";
        echo "<a href='?page=1&$query_string'>First</a>";
        echo "<a href='?page=$prev&$query_string'>Previous</a>";

        for ($p = 1; $p <= $this->page_count; $p++) {
            $c = $p == $this->page ? 'active' : '';
            echo "<a href='?page=$p&$query_string' class='$c'>$p</a>";
        }

        echo "<a href='?page=$next&$query_string'>Next</a>";
        echo "<a href='?page=$this->page_count&$query_string'>Last</a>";
        echo "</nav>";
    }
}
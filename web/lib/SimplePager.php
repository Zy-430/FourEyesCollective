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
}
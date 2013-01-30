<?php
/*
 *	mysql���ݿ� DB��
 *	@package	db
 *	@author		yytcpt(��Ӱ)
 *	@version	2008-03-27
 *	@copyrigth	http://www.d5s.cn/
 */
class Db {
	var $connection_id = "";
	var $pconnect = 0;
	var $shutdown_queries = array();
	var $queries = array();
	var $query_id = "";
	var $query_count = 0;
	var $record_row = array();
	var $failed = 0;
	var $halt = "";
	var $query_log = array();
	function connect($db_config){
		if ($this->pconnect){
			$this->connection_id = mysql_pconnect($db_config["hostname"], $db_config["username"], $db_config["password"]);
		}else{
			$this->connection_id = mysql_connect($db_config["hostname"], $db_config["username"], $db_config["password"]);
		}
		if ( ! $this->connection_id ){
			$this->halt("Can not connect MySQL Server");
		}
		if ( ! @mysql_select_db($db_config["database"], $this->connection_id) ){
			$this->halt("Can not connect MySQL Database");
		}
		if ($db_config["charset"]) {
			@mysql_unbuffered_query("SET NAMES '".$db_config["charset"]."'");
		}
		return true;
	}
	//����SQL ��ѯ�������ؽ����
    function query($sql, $query_type='mysql_query'){
        $this->query_id = $query_type($sql, $this->connection_id);
		# modify by xdj
		#$this->queries[] = $sql;
		# modify end
        if (! $this->query_id ) {
            $this->halt("��ѯʧ��:\n$sql");
		}
		$this->query_count++;
		#$this->query_log[] = $sql;
        return $this->query_id;
    }
	//����SQL ��ѯ��������ȡ�ͻ���������
	function query_unbuffered($sql=""){
		return $this->query($sql, 'mysql_unbuffered_query');
	}
	//�ӽ������ȡ��һ����Ϊ��������
    function fetch_array($result = ""){
    	if ($result == "") $result = $this->query_id;
        $this->record_row = @mysql_fetch_array($result, MYSQL_ASSOC);
        return $this->record_row;
    }
	function shutdown_query($query_id = ""){
		$this->shutdown_queries[] = $query_id;
    }
	//ȡ�ý�������е���Ŀ������ INSERT��UPDATE ���� DELETE
	function affected_rows() {
        return @mysql_affected_rows($this->connection_id);
    }
	//ȡ�ý�������е���Ŀ������ SELECT �����Ч
    function num_rows($query_id="") {
		if ($query_id == "") $query_id = $this->query_id;
        return @mysql_num_rows($query_id);
    }
	//������һ�� MySQL �����еĴ�����Ϣ�����ֱ���
	function get_errno(){
		$this->errno = @mysql_errno($this->connection_id);
		return $this->errno;
	}
	//ȡ����һ�� INSERT ���������� ID
    function insert_id(){
        return @mysql_insert_id($this->connection_id);
    }
	//�õ���ѯ����
    function query_count() {
        return $this->query_count;
    }
	//�ͷŽ���ڴ�
    function free_result($query_id=""){
   		if ($query_id == "") $query_id = $this->query_id;
    	@mysql_free_result($query_id);
    }
	//�ر� MySQL ����
    function close_db(){
    	if ( $this->connection_id ) return @mysql_close( $this->connection_id );
    }
	//�г� MySQL ���ݿ��еı�
    function get_table_names(){
    	global $db_config;
		$result = mysql_list_tables($db_config["database"]);
		$num_tables = @mysql_numrows($result);
		for ($i = 0; $i < $num_tables; $i++) {
			$tables[] = mysql_tablename($result, $i);
		}
		mysql_free_result($result);
		return $tables;
   	}
	//�ӽ������ȡ������Ϣ����Ϊ���󷵻أ�ȡ�������ֶ�
    function get_result_fields($query_id=""){
   		if ($query_id == "") $query_id = $this->query_id;
		while ($field = mysql_fetch_field($query_id)) {
            $fields[] = $field;
		}
		return $fields;
   	}
	//������ʾ
    function halt($the_error=""){
		$message = $the_error."<br/>\r\n";
		$message.= $this->get_errno() . "<br/>\r\n";
		$sql = "INSERT INTO `db_error`(pagename, errstr, timer) VALUES('".$_SERVER["PHP_SELF"]."', '".addslashes($message)."', ".time().")";
		@mysql_unbuffered_query($sql);
		if (DEBUG==true){
			echo "<html><head><title>MySQL ���ݿ����</title>";
			echo "<style type=\"text/css\"><!--.error { font: 11px tahoma, verdana, arial, sans-serif, simsun; }--></style></head>\r\n";
			echo "<body>\r\n";
			echo "<blockquote>\r\n";
			echo "<textarea class=\"error\" rows=\"15\" cols=\"100\" wrap=\"on\" >" . htmlspecialchars($message) . "</textarea>\r\n";
			echo "</blockquote>\r\n</body></html>";
			exit;
		}
    }
	function __destruct(){
		# modify by xdj
		#$this->shutdown_queries = array();
		unset($this->shutdown_queries);
		# modify end
		$this->close_db();
	}
	function sql_select($tbname, $where="", $limit=0, $fields="*", $orderby="ID", $sort="ASC"){
		$sql[] = 'SELECT ';
		$sql[] = $fields;
		$sql[] = ' FROM `';
		$sql[] = $tbname;
		$sql[] = '`';
		if (isset($where[0])) {
			$sql[] = ' WHERE ';
			$sql[] = $where;
		}
		if (isset($orderby[0])) {
			$sql[] = ' ORDER BY ';
			$sql[] = $orderby;
			if (isset($sort[0])) {
				$sql[] = ' ';
				$sql[] = $sort;
			}
		}
		if ($limit) {
			$sql[] = ' LIMIT ';
			$sql[] = $limit;
		}
		return implode($sql);
	}
	function sql_insert($tbname, $row){
	    $sqlfield = '';
	    $sqlvalue = '';
		foreach ($row as $key=>$value) {
			$sqlfield .= $key.",";
			$sqlvalue .= "'".mysql_escape_string($value)."',";
		}
		return "INSERT INTO `".$tbname."`(".substr($sqlfield, 0, -1).") VALUES (".substr($sqlvalue, 0, -1).")";
	}
    function sql_replace($tbname, $row){
	    $sqlfield = '';
	    $sqlvalue = '';
		foreach ($row as $key=>$value) {
			$sqlfield .= $key.",";
			$sqlvalue .= "'".mysql_escape_string($value)."',";
		}
		return "REPLACE INTO `".$tbname."`(".substr($sqlfield, 0, -1).") VALUES (".substr($sqlvalue, 0, -1).")";
	}
	function sql_update($tbname, $row, $where){
	    $sqlud = '';
		foreach ($row as $key=>$value) {
			$sqlud .= $key."= '".mysql_escape_string($value)."',";
		}
		return "UPDATE `".$tbname."` SET ".substr($sqlud, 0, -1)." WHERE ".$where;
	}
	function sql_delete($tbname, $where){
		return "DELETE FROM `".$tbname."` WHERE ".$where;
	}
	//������һ����¼
	function row_insert($tbname, $row){
		$sql = $this->sql_insert($tbname, $row);
		return $this->query_unbuffered($sql);
	}
    //�滻һ����¼
	function row_replace($tbname, $row){
		$sql = $this->sql_replace($tbname, $row);
		return $this->query_unbuffered($sql);
	}
	//����ָ����¼
	function row_update($tbname, $row, $where){
		$sql = $this->sql_update($tbname, $row, $where);
		return $this->query_unbuffered($sql);
	}
	//ɾ�����������ļ�¼
	function row_delete($tbname, $where){
		$sql = $this->sql_delete($tbname, $where);
		return $this->query_unbuffered($sql);
	}
	/*	����������ѯ���������м�¼
	 *	$tbname ����, $where ��ѯ����, $limit ���ؼ�¼, $fields �����ֶ�
	 */
	function row_select($tbname, $where="", $limit=0, $fields="*", $orderby="ID", $sort="ASC"){
		$sql = $this->sql_select($tbname, $where, $limit, $fields, $orderby, $sort);
		return $this->row_query($sql);
	}
	//��ϸ��ʾһ����¼
	function row_select_one($tbname, $where, $fields="*", $orderby=null){
		$sql = $this->sql_select($tbname, $where, 1, $fields, $orderby);
		return $this->row_query_one($sql);
	}
	function row_query($sql){
		$rs	 = $this->query($sql);
		$rs_num = $this->num_rows($rs);
		$rows = array();
		for($i=0; $i<$rs_num; $i++){
			$rows[] = $this->fetch_array($rs);
		}
		$this->free_result($rs);
		return $rows;
	}
	function row_query_one($sql){
		$rs	 = $this->query($sql);
		$row = $this->fetch_array($rs);
		$this->free_result($rs);
		return $row;
	}
	//����ͳ��
	function row_count($tbname, $where=FALSE){
		$sql = "SELECT count(*) as row_sum FROM `".$tbname."` ".($where?" WHERE ".$where:"");
		$row = $this->row_query_one($sql);
		return $row["row_sum"];
	}
	//Max����
	function row_max($tbname, $where=""){
		$sql = "SELECT MAX(ID) as row_max FROM `".$tbname."` ".($where?" WHERE ".$where:"");
		$row = $this->row_query_one($sql);
		return $row["row_max"];
	}
}

/*
 * <USAGE>
 *
    $db_config["hostname"]    = "127.0.0.1";    //��������ַ
    $db_config["username"]    = "root";        //���ݿ��û���
    $db_config["password"]    = "root";        //���ݿ�����
    $db_config["database"]    = "wap_blueidea_com";        //���ݿ�����
    $db_config["charset"]        = "utf8";
    include('db.php');
    $db    = new db();
    $db->connect($db_config);
    //������ѯ�� table_name �� cid=1�����м�¼��
    $row = $db->row_select('table_name', 'cid=1');
 */

<?php
namespace marianojwl\MediaProcessor {
    class RequestRepository extends Repository {
        /*
        protected $table;
        public function __construct($tableName) {
            parent::__construct();
            $this->table = $tableName;
        }
        */
        public function addQuick($media_source_id, $template_id, $status, $settings, $settings_hash = null) {
          $sql = "INSERT INTO ".$this->table." (media_source_id, template_id, status, settings) VALUES ('".$media_source_id."', '".$template_id."', '".$status."', '".$settings."')";
          if($settings_hash !== null)
            $sql = "INSERT INTO ".$this->table." (media_source_id, template_id, status, settings, settings_hash) VALUES ('".$media_source_id."', '".$template_id."', '".$status."', '".$settings."', '".$settings_hash."')";
          $result = $this->conn->query($sql);
          $success = false;
          $message = "";
          $error = "";
          $data = null;
          if($result) {
              $success = true;
              $message = "Request added successfully";
              $data = ["insert_id"=>$this->conn->insert_id];
          } else {
              $error = $this->conn->error;
              $message = "Error adding request";
          }
          return json_encode(["success"=>$success, "message"=>$message, "error"=>$error, "data"=>$data]);
        }
        public function add($request) {
          $sql = "INSERT INTO ".$this->table." (media_source_id, template_id, status, settings) VALUES ('".$request->getResource()->getId()."', '".$request->getTemplate()->getId()."', '".$request->getStatus()."', '".$request->getSettings()."')";
          $result = $this->conn->query($sql);
          $success = false;
          $message = "";
          $error = "";
          $data = null;
          if($result) {
              $success = true;
              $message = "Request added successfully";
              $data = ["insert_id"=>$this->conn->insert_id];
          } else {
              $error = $this->conn->error;
              $message = "Error adding request";
          }
          return json_encode(["success"=>$success, "message"=>$message, "error"=>$error, "data"=>$data]);
        }
        public function getById(int $id) {
            $obj = null;
            $query = "SELECT * FROM ".$this->table." WHERE id='".$id."'";
            $result = $this->conn->query($query);
            if($row = $result->fetch_assoc()) {
                $rsr = $this->mp->getMediaSourcesRepository();
                $resource = $rsr->getById($row["media_source_id"]);
                $rsr->closeConnection();

                $tr = $this->mp->getTemplateRepository();
                $template = $tr->getById($row["template_id"]);
                $tr->closeConnection();

                $obj = new Request($row["id"], $resource, $template, $row["status"], $row["processed_path"], $row["processed_thumb_path"], $this);
                if(isset($row["settings"])) $obj->setSettings($row["settings"]);
                if(isset($row["settings_hash"])) $obj->setSettingsHash($row["settings_hash"]);
            }
                
            return $obj;
        }

        public function getNextN(int $n) {
           
            $objs = [];
            $query = "SELECT * FROM ".$this->table." WHERE status='pending' ORDER BY id ASC LIMIT ".$n;
            $result = $this->conn->query($query);
            
            while($row = $result->fetch_assoc()) {
                $rsr = $this->mp->getMediaSourcesRepository();
                $resource = $rsr->getById($row["media_source_id"]);
                //$rsr->closeConnection();
                
                $tr = $this->mp->getTemplateRepository();
                $template = $tr->getById($row["template_id"]);
                
                $newRequest = new Request($row["id"], $resource, $template, $row["status"], $row["processed_path"], $row["processed_thumb_path"], $this);
                if(isset($row["settings"])) $newRequest->setSettings($row["settings"]);
                if(isset($row["settings_hash"])) $newRequest->setSettingsHash($row["settings_hash"]);
                $template->setRequest($newRequest);
                $objs[] = $newRequest;
                //$tr->closeConnection();
            }
            
            return $objs;
        }
        /*
        public function setStatus(string $status,$request) {
            $sql = "UPDATE ".$this->table." SET status='".$status."' WHERE id='".$request->getId()."'";
            $result = $this->conn->query($sql);
            if($result)
                $request->setStatus($status);
            return $request;
        }
        */
        public function save(Request $request) {
            $sql = "UPDATE ".$this->table." SET status='".$request->getStatus()."', processed_path='".$request->getProcessedPath()."' , processed_thumb_path='".$request->getProcessedThumbPath()."' WHERE id='".$request->getId()."'";
            $result = $this->conn->query($sql);
            return $result;
        }
        public function getByParams($params) {
            $objs = [];
            $query = "SELECT * FROM ".$this->table." WHERE ";
            foreach($params as $key=>$value) {
                $query .= $key."='".$value."' AND ";
            }
            $query = substr($query, 0, -4);
            $result = $this->conn->query($query);
            while($row = $result->fetch_assoc()) {
                $objs[] = $row;
            }
            return $objs;
        }

    }
}
<?php
abstract class CronTask extends Shell{
    public function execute(){
        echo "execute called";
    }
}

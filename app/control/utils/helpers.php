<?php

class helpers 
{
    public static function formatDate($date)
    {
        if($date == null){
            return 'Não possui data';
            }
        $dt = new DateTime($date);
        return $dt->format('d/m/Y  H:i:s');
    }
}
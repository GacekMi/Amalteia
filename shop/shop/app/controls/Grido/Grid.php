<?php

namespace App\Controls\Grido;

/**
 * Base of grid.
 */
class MyGrid extends \Grido\Grid {

    /**
     * Custom condition callback for filter birthday.
     * @param string $value
     * @return array|NULL
     */
    public function registeredFilterCondition($value) {
        $date = explode('-', $value);
        $date1 = explode('.', $date[0]);
        $date2 = explode('.', $date[1]);
        foreach ($date1 as &$val) {
            $val = (int) $val;
        }

        foreach ($date2 as &$val) {
            $val = (int) $val;
        }

        return count($date) == 2 ? array('registered', "BETWEEN '{$date1[2]}-{$date1[1]}-{$date1[0]} 00:00:00' AND '{$date2[2]}-{$date2[1]}-{$date2[0]} 23:59:59'", NULL) : NULL;
    }

    public function birthFilterCondition($value) {
        $date = explode('-', $value);
        $date1 = explode('.', $date[0]);
        $date2 = explode('.', $date[1]);
        foreach ($date1 as &$val) {
            $val = (int) $val;
        }

        foreach ($date2 as &$val) {
            $val = (int) $val;
        }

        return count($date) == 2 ? array('birth', "BETWEEN '{$date1[2]}-{$date1[1]}-{$date1[0]}' AND '{$date2[2]}-{$date2[1]}-{$date2[0]}'", NULL) : NULL;
    }
    
    public function createFilterCondition($value) {
        $date = explode('-', $value);
        $date1 = explode('.', $date[0]);
        $date2 = explode('.', $date[1]);
        foreach ($date1 as &$val) {
            $val = (int) $val;
        }

        foreach ($date2 as &$val) {
            $val = (int) $val;
        }

        return count($date) == 2 ? array('create_date', "BETWEEN '{$date1[2]}-{$date1[1]}-{$date1[0]}' AND '{$date2[2]}-{$date2[1]}-{$date2[0]}'", NULL) : NULL;
    }
    
    public function changeFilterCondition($value) {
        $date = explode('-', $value);
        $date1 = explode('.', $date[0]);
        $date2 = explode('.', $date[1]);
        foreach ($date1 as &$val) {
            $val = (int) $val;
        }

        foreach ($date2 as &$val) {
            $val = (int) $val;
        }

        return count($date) == 2 ? array('change_date', "BETWEEN '{$date1[2]}-{$date1[1]}-{$date1[0]}' AND '{$date2[2]}-{$date2[1]}-{$date2[0]}'", NULL) : NULL;
    }

     public function secondCDateFilterCondition($value) {
        $date = explode('-', $value);
        $date1 = explode('.', $date[0]);
        $date2 = explode('.', $date[1]);
        foreach ($date1 as &$val) {
            $val = (int) $val;
        }

        foreach ($date2 as &$val) {
            $val = (int) $val;
        }

        return count($date) == 2 ? array('second_c_date', "BETWEEN '{$date1[2]}-{$date1[1]}-{$date1[0]}' AND '{$date2[2]}-{$date2[1]}-{$date2[0]}'", NULL) : NULL;
    }
    
     public function dateFilterCondition($value) {
        $date = explode('-', $value);
        $date1 = explode('.', $date[0]);
        $date2 = explode('.', $date[1]);
        foreach ($date1 as &$val) {
            $val = (int) $val;
        }

        foreach ($date2 as &$val) {
            $val = (int) $val;
        }

        return count($date) == 2 ? array('date', "BETWEEN '{$date1[2]}-{$date1[1]}-{$date1[0]}' AND '{$date2[2]}-{$date2[1]}-{$date2[0]}'", NULL) : NULL;
    }
    
    public function lastloginFilterCondition($value) {
        $date = explode('.', $value);
        foreach ($date as &$val) {
            $val = (int) $val;
        }

        return count($date) == 3 ? array('lastlogin', "BETWEEN '{$date[2]}-{$date[1]}-{$date[0]} 00:00:00' AND '{$date[2]}-{$date[1]}-{$date[0]} 23:59:59'", NULL) : NULL;
    }

    /**
     * @param string $name
     * @param string $label
     * @return Components\Columns\Boolean
     */
    public function addColumnBoolean($name, $label) {
        $column = new Components\Columns\Boolean($this, $name, $label);

        $header = $column->headerPrototype;
        $header->style['width'] = '2%';
        $header->class[] = 'center';

        return $column;
    }

}

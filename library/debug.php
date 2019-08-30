<?php
/******************************************************************************
 * Catlair PHP Copyright (C) 2019  a@itserv.ru
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 ******************************************************************************
 *
 * Debug system
 * Catlair PHP
 *
 * Create: 2017
 * Update:
 * 05.03.2019 - Add Dump
 * 23.08.2019 - Add colord
 *
 * still@itserv.ru
 */
/* Log destination */
define ("ldFile", 'file');
define ("ldConsole", 'console');
/* Type Message */
define ("ltBeg", '>');
define ("ltEnd", '<');
define ("ltInf", 'i');
define ("ltErr", 'X');
define ("ltWar", 'W');
define ("ltDeb", '#');
/* Escape colors */
define ("ESC_INK_DEFAULT",  "\e[39m");
define ("ESC_INK_BLACK",    "\e[30m");
define ("ESC_INK_BROWN",    "\e[31m");
define ("ESC_INK_GREEN",    "\e[32m");
define ("ESC_INK_GOLD",     "\e[33m");
define ("ESC_INK_BLUE",     "\e[34m");
define ("ESC_INK_PURPUR",   "\e[35m");
define ("ESC_INK_CYAN",     "\e[36m");
define ("ESC_INK_SILVER",   "\e[37m");
define ("ESC_INK_GREY",     "\e[90m");
define ("ESC_INK_RED",      "\e[91m");
define ("ESC_INK_LIME",     "\e[92m");
define ("ESC_INK_YELLOW",   "\e[93m");
define ("ESC_INK_SKY",      "\e[94m");
define ("ESC_INK_MAGENTA",  "\e[95m");
define ("ESC_INK_AQUA",     "\e[96m");
define ("ESC_INK_WHITE",    "\e[97m");
define ("chLine", '--------------------------------------------------------------------------------');
define ("chEnd", chr(10));
define ("EOL", chr(10)); /* Do not use. It will be delited. Must use chEnd. */
class TLog
{
    /* Private declaration */
    private $Handle = -1; /* Handle for file */
    private $MomentLast = 0; /* Moment begin for last message*/
    private $MomentCurrent = 0; /* Moment begin for current message*/
    private $InLine = false; /*Debugger have begun new line*/
    private $LineType = ''; /*Type of current line*/
    private $CurrentTrace = null; /* Current trace information */
    private $CurrentTraceLabel = ''; /* Trace label for current line */
    private $CurrentString = ''; /* Full log string message for write to destination with all charaters */
    private $PureString = ''; /* String message for without escape and control characters */
    private $Stack = []; /* Stack for jobs is controlled by Begin and End.*/
    private $TraceResult = [];
    /* Public declarations */
    public $Colored = true; /* Color out enable or disable */
    public $ShowMoment = false; /* Begin line moment out to log */
    public $Destination = ldConsole; /* Destination for log ldConsole, ldFile  */
    public $TimeWarning = 1.0; /* Line highlight when timeout more value*/
    public $Enabled = true; /* Enable or disable log */
    public $Job = true; /* Enable/disbale job line */
    public $Debug = true; /* Enable/disable debug messages */
    public $Info = true; /* Enable/disable info messeges */
    public $Error = true; /* Enable/disable error messages*/
    public $Warning = true; /* Enable/disable warning messages */
    public $Path = ''; /* Path for log file */
    /* Write current $AString:string to destination */
    public function &Write($AString)
    {
        if ($this->Enabled)
        {
            if ($this->Handle == -1 && $this->Destination == ldFile)
            {
                $File = $this->Path .'/'. $this->File;
                if ($File != "")
                {
                    /* Create folder if it not exists */
                    if (!file_exists($this->Path)) mkdir($this->Path, FILE_RIGHT, true);
                    /* Open file*/
                    $this->Handle = fopen($File, 'w+');
                }
                else $this->Handle = -1;
            }
            if ($this->Handle == -1)
            {
                /*Write to console*/
                print($AString);
            }
            else
            {
                /*Write to file*/
                fwrite($this->Handle, $AString);
            }
        }
        return $this;
    }
    /*
    Text $AString:string is outed to current line
    */
    private function &Store($AString, $APure)
    {
        $this->CurrentString .= $AString;
        if ($APure) $this->PureString .= $AString;
        return $this;
    }
    /*
    Out trace information
    */
    private function TraceOut()
    {
        function TraceSort ($a, $b)
        {
            if ($a['Delta']>$b['Delta']) return -1;
            else if ($a['Delta']<$b['Delta']) return 1;
                else return 0;
        }
        $this->Debug()->Text('Trace information');
        uasort($this->TraceResult, 'TraceSort');
        foreach ($this->TraceResult as $Key => $Value)
        {
            $this -> Debug() ->  Text ($Key) -> Color(ESC_INK_GREY) -> Tab(100);
            $this -> Color(ESC_INK_DEFAULT) -> Text( str_pad((string)(number_format($Value['Delta'] * 1000, 2,'.',' ')), 15, ' ', STR_PAD_LEFT) . 'ms');
            $this -> Text( str_pad($Value['Count'], 10, '.', STR_PAD_LEFT));
        }
        $this->End();
    }
    /*
    Begin of new line with type $AType:ltBeg,ltEnd,ltWar,ltInf,ltDeb,ltErr
    */
    public function &LineBegin($AType)
    {
        /*If line is begin then close line*/
        if ($this->InLine) $this->LineEnd();
        $this->CurrentString = "";
        $this->PureString = "";
        /*Begin new line*/
        $this->InLine = true;
        $this->LineType = $AType;
        $this->MomentCurrent = microtime(true);
        /* Get trace information for line */
        $this->CurrentTrace = debug_backtrace();
        /* Check log enabled and other settings */
        if
        (
            $this->Enabled &&
            (
                $AType==ltErr && $this->Error ||
                $AType==ltDeb && $this->Debug ||
                $AType==ltWar && $this->Warning ||
                $AType==ltInf && $this->Info ||
                ($AType==ltBeg || $AType==ltEnd) && $this->Job
            )
        )
        {
            /* Calculate delta */
            if ($this->MomentLast!=0) $Delta = $this->MomentCurrent - $this->MomentLast;
            else $Delta = 0;
            $Delta=$Delta*1000;
            /* Moment line */
            if ($this->ShowMoment) $this->Color(ESC_INK_SILVER) -> Text(date('Y-m-d H:i:s'));
            /* Interline timeout */
            if ($Delta > $this->TimeWarning) $this->Color(ESC_INK_RED);
            else $this->Color(ESC_INK_GREY);
            $this->Text(str_pad((string) (number_format($Delta, 2, '.', ' ')), 9, ' ', STR_PAD_LEFT));
            /* Trace information */
            if (count($this->CurrentTrace) > 1)
            {
                $this->Color(ESC_INK_SILVER) -> Text(str_pad((string) $this->CurrentTrace[1]['line'], 6, ' ', STR_PAD_LEFT). ' ');
                $this->Color(ESC_INK_BLUE) -> Text(basename($this->CurrentTrace[1]['file']).' ');
            }
            if (count($this->CurrentTrace) > 2)
            {
                $this->Color(ESC_INK_GOLD) -> Text($this->CurrentTrace[2]['function']);
            }
            /* Tabulate */
            $this->Color(ESC_INK_GREY);
            $this->Tab(70);
            $this->Color(ESC_INK_DEFAULT);
            /* Color */
            switch ($this->LineType)
            {
                case ltDeb: $this->Color(ESC_INK_GREY); break;
                case ltInf: $this->Color(ESC_INK_BLUE); break;
                case ltWar: $this->Color(ESC_INK_YELLOW); break;
                case ltErr: $this->Color(ESC_INK_RED); break;
                case ltBeg: $this->Color(ESC_INK_GREEN); break;
                case ltEnd: $this->Color(ESC_INK_GREEN); break;
            }
            $this -> Text(' '. $AType . ' ') -> Color(ESC_INK_GREY) -> Text(str_repeat('.', count($this->Stack) * 3 )) ->  Color(ESC_INK_DEFAULT);
        }
        return $this;
    }
    /* Close current line */
    private function &LineEnd()
    {
        if ($this->InLine)
        {
            $this->MomentLast = $this->MomentCurrent;
            /* Write End of line */
            $this->EOL();
            $this->InLine = false;
        }
        /* Write to file*/
        $this->Write($this->CurrentString);
        return $this;
    }
    /* Start debug with $AEnabled:boolean*/
    public function &Start($AEnabled)
    {
        $this -> Enabled = $AEnabled;
        /* reset job stack */
        $this->Stack = [];
        /* reset trace array */
        $this->Trace = [];
        /* write last moment */
        $this -> CurrentLast = microtime(true);
        return $this;
    }
    /*
     Stop debug
     */
    public function &Stop()
    {
        $this->MomentEnd = microtime(true);
        /* Close Last line */
        $this->LineEnd();
        $this->TraceOut();
        /* Close file if it was opened. */
        if ($this->Handle != -1) fclose($this->Handle);
        return $this;
    }
    /*
    Text $AString:string is outed to log
    */
    public function &Text($AString)
    {
        $this->Store($AString, true);
        return $this;
    }
    /*
    Color $AColor:ESC_* is set for next output
    $AColor - escape sequence from constant ESC_*
    */
    public function &Color($AColor)
    {
        if ($this->Colored) $this->Store($AColor, false);
        return $this;
    }
    /*
    Set tabulate to $APosition:integer for current line
    */
    public function &Tab($APosition)
    {
        $l = strlen($this->PureString);
        if ($l<$APosition) $this -> Text(str_repeat('.', $APosition-$l));
        return $this;
    }
    /*
    write $AValue to current line
    */
    public function &Value($AValue)
    {
        $Type = gettype($AValue);
        switch ($Type)
        {
            case 'string':
                $l=strlen($AValue);
                if ($l > 81) $Value = substr($AValue, 0, 80).'...'.$l;
                else $Value = $AValue;
                $Type='s';
            break;
            case 'array':
                $Value = (string)count($AValue);
            break;
            case 'boolean':
                $Value = (string)$AValue;
                $Type='b';
            break;
            case 'integer':
                $Value = (integer)$AValue;
                $Type='i';
            break;
            case 'double':
                $Value = $AValue;
                $Type='d';
            break;
            case 'object':
                $Value = NULL;
            break;
            case 'RESOURCE':
                $Value = NULL;
            break;
            case 'NULL':
                $Value = NULL;
            break;
        }
        $this->Color(ESC_INK_GREY)->Text($Type);
        if ($Value) $this->Text(":")->Color(ESC_INK_AQUA)->Text($Value);
        $this->Color(ESC_INK_DEFAULT);
        return $this;
    }
    /*
     Out parameter with $AName:string and $AValue:any to current line for result [Name = type:Value]
     */
    public function &Param($AName, $AValue)
    {
        $this->Color(ESC_INK_GREY)->Text('[');
        $this->Color(ESC_INK_GREEN)->Text($AName)->Color(ESC_INK_GREY)->Text('=')->Value($AValue);
        $this->Color(ESC_INK_GREY)->Text(']')->Color(ESC_INK_DEFAULT);
        return $this;
    }
    /* Set trace label from $ALabel */
    public function &Label($ALabel)
    {
        $this->CurrentTraceLabel=$ALabel;
        return $this;
    }
    /* Job begin for new line*/
    public function &Begin()
    {
        $this->LineBegin(ltBeg);
        /* Store trace information */
        if (count($this->CurrentTrace) > 2)
        {
            $this->Color(ESC_INK_GOLD)->Text($this->CurrentTrace[2]['function'])->Color(ESC_INK_DEFAULT);
            foreach ($this->CurrentTrace[2]['args'] as $Value)
            {
                $this->Color(ESC_INK_GREY)->Text(' [')->Color(ESC_INK_DEFAULT);
                $this->Value($Value);
                $this->Color(ESC_INK_GREY)->Text(']')->Color(ESC_INK_DEFAULT);
            }
            $this->CurrentTraceLabel = $this->CurrentTrace[2]['function'];
        }
        else
        {
            $this->CurrentTraceLabel = 'main';
        }
        /* Store trace information */
        array_push($this->Stack, ['Moment'=>$this->MomentCurrent, 'TraceLabel'=>$this->CurrentTraceLabel]);
        return $this;
    }
    /* Job end */
    public function &End()
    {
        if (count($this->Stack) > 0) $StackRecord = array_pop($this->Stack);
        else $StackRecord = null;
        $this->LineBegin(ltEnd);
        if ( $StackRecord!=null )
        {
            /* Calculate delta from job begin */
            $TraceDelta = $this->MomentCurrent - $StackRecord['Moment'];
            $this->Color(ESC_INK_GREY);
            $this->Text((string)(number_format($TraceDelta*1000, 2,'.',' ')).'ms ');
            $this->Color(ESC_INK_DEFAULT);
        }
        else
        {
            $this->Color(ESC_INK_RED)->Text(' Tracert heracly error ')->Color(ESC_INK_DEFAULT);
            $TraceDelta = 0;
        }
        if (array_key_exists($this->CurrentTraceLabel, $this->TraceResult))
        {
            $this->TraceResult[$this->CurrentTraceLabel]['Delta'] += $TraceDelta;
            $this->TraceResult[$this->CurrentTraceLabel]['Count'] ++;
        }
        else
        {
            $this->TraceResult[$this->CurrentTraceLabel]['Delta'] = $TraceDelta;
            $this->TraceResult[$this->CurrentTraceLabel]['Count'] = 1;
        }
        return $this;
    }
    /*New debug line*/
    public function &Debug()
    {
        $this->LineBegin(ltDeb);
        return $this;
    }
    /*New information line*/
    public function &Info()
    {
        $this->LineBegin(ltInf);
        return $this;
    }
    /* New warning line */
    public function &Warning()
    {
        $this->LineBegin(ltWar);
        return $this;
    }
    /* New error line */
    public function &Error()
    {
        $this->LineBegin(ltErr);
        return $this;
    }
    /* New dump line */
    public function &Dump($AArray)
    {
        $this->Begin();
        foreach ($AArray as $Key=>$Value) $this -> Debug() -> Param($Key,$Value);
        $this->End();
        return $this;
    }
    /* New error line */
    public function &EOL()
    {
        $this->Text(chEnd);
        return $this;
    }
}

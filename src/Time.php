<?php

namespace utils;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use JetBrains\PhpStorm\Internal\TentativeType;
use JetBrains\PhpStorm\Pure;

class Time extends DateTime implements \JsonSerializable{

    /*
    private DateTime $date;

    public function __construct(int|DateTime|string $date, ?string $format = null){
        if($date instanceof DateTime){
            $this->date = $date;
        }else if(is_int($date)){
            $this->date = new DateTime();
            $this->date->setTimestamp($date);
        }else if(is_string($date) && $format !== null){
            $date = DateTime::createFromFormat($format, $date);
            if($date !== false){
                $this->date = $date;
            }else{
                throw new DateException('Failed to parse Date from format');
            }
        }else{
            throw new DateException('Cannot create Date');
        }
    }

    public function getDate(): DateTime{
        return $this->date;
    }
    public function format(string $format): string{
        return $this->getDate()->format($format);
    }
    */

    /**
     * @param $format
     * @param $datetime
     * @param DateTimeZone|null $timezone
     * @return false|static
     * @throws Exception
     */
    public static function createFromFormat($format, $datetime, DateTimeZone $timezone = null): static|false{
        $result = parent::createFromFormat($format, $datetime, $timezone);
        if($result === false)
            return false;
        return new static($result->format(DATE_RFC3339_EXTENDED));
    }

    /**
     * @param int $timestamp
     * @param bool $milliseconds
     * @return false|static
     */
    public static function createFromTimestamp(int $timestamp, bool $milliseconds = true): static|false{
        return static::createFromFormat( 'U', $milliseconds ? intval($timestamp/1000) : $timestamp);
    }

    /**
     * @param int $timestamp
     * @param bool $milliseconds
     * @return bool
     */
    #[Pure]
    public static function isTimeInPast(int $timestamp, bool $milliseconds = true): bool{
        return $timestamp < Time::getCurrentTimestamp($milliseconds);
    }

    /**
     * @param bool $milliseconds
     * @return int
     */
    #[Pure]
    public static function getCurrentTimestamp(bool $milliseconds = true): int{
        return $milliseconds ? intval(round(microtime(true) * 1000)) : intval(round(microtime(true)));
    }

    /**
     * @return Time
     */
    public static function now(): Time{
        return new Time();
    }

    /**
     * @param string $period
     * @return string
     */
    public static function translatePeriod(string $period): string{
        return str_replace(
            [
                'years',
                'months',
                'weeks',
                'days',
                'hours',
                'minutes',
                'seconds'
            ],
            [
                'Jahre',
                'Monate',
                'Wochen',
                'Tage',
                'Stunden',
                'Minuten',
                'Sekunden'
            ],
            strtolower($period));
    }

    /**
     * @param string $format
     * @param bool $translate
     * @return string
     */
    public function format($format, bool $translate = true): string{
        return $translate ? Util::translateDate(parent::format($format)) : parent::format($format);
    }

    /**
     * @return bool
     */
    public function isToday(): bool{
        return $this->format('d.m.Y') === date('d.m.Y');
    }

    /**
     * @param Time|int|null $from
     * @return bool
     */
    public function isInPast(Time|int|null $from = null): bool{
        if($from instanceof Time)
            $from = $from->getTimestamp();
        return $this->getTimestamp() < ($from !== null ? $from : Util::getTimestamp(false));
    }

    /**
     * @param Time|int|null $from
     * @return bool
     */
    public function isInPastOrNow(Time|int|null $from = null): bool{
        if($from instanceof Time)
            $from = $from->getTimestamp();
        return $this->getTimestamp() <= ($from !== null ? $from : Util::getTimestamp(false));
    }

    /**
     * @param Time|int|null $from
     * @return bool
     */
    public function isInFuture(Time|int|null $from = null): bool{
        if($from instanceof Time)
            $from = $from->getTimestamp();
        return $this->getTimestamp() > ($from !== null ? $from : Time::getCurrentTimestamp(false));
    }

    /**
     * @param int $days
     * @param Time|int|null $from
     * @return bool if the time is in one of the next days (not larger and not in past)
     */
    public function isInNextDays(int $days, Time|int|null $from = null): bool{
        if($from instanceof Time)
            $from = $from->getTimestamp();
        if(!$this->isInPast($from)){
            if($from === null)
                $from = Time::getCurrentTimestamp(false);
            $maxTime = $from + ($days * 24 * 60 * 60);
            return $this->getTimestamp() < $maxTime;
        }
        return false;
    }

    /**
     * @param int $days
     * @return bool if the time is in one of the last days (not bigger and not in future)
     */
    public function isInLastDays(int $days): bool{
        if(!$this->isInFuture()){
            $minTime = Time::getCurrentTimestamp(false) - ($days * 24 * 60 * 60);
            return $this->getTimestamp() > $minTime;
        }
        return false;
    }

    /**
     * @param bool $timestamp
     * @return int
     */
    public function getMicroseconds(bool $timestamp = false): int{
        return $timestamp ? intval($this->getTimestamp() . $this->format('s.u')) : intval($this->format('u'));
    }

    /**
     * @param int $microseconds
     * @return Time
     */
    public function setMicroseconds(int $microseconds): Time{
        $this->setTime($this->getHours(), $this->getMinutes(), $this->getSeconds(), $microseconds);
        return $this;
    }

    /**
     * @param bool $timestamp
     * @return int
     */
    public function getMilliseconds(bool $timestamp = false): int{
        return $timestamp ? intval($this->getTimestamp() . $this->format('s0')) : intval($this->format('s'));
    }

    /**
     * @param bool $pad
     * @return int|string
     */
    public function getSeconds(bool $pad = false): int|string{
        return $pad ? $this->format('s') : intval($this->format('s'));
    }

    /**
     * @param int $seconds
     * @param bool $keep_time
     * @return Time
     */
    public function setSeconds(int $seconds, bool $keep_time = false): Time{
        if($keep_time)
            $this->setTime($this->getHours(), $this->getMinutes(), $seconds, $this->getMicroseconds());
        else
            $this->setTime($this->getHours(), $this->getMinutes(), $seconds);
        return $this;
    }

    /**
     * @param int $seconds
     * @return Time
     */
    public function addSeconds(int $seconds): self{
        if($seconds < 0){
            $this->sub(new DateInterval('PT' . ($seconds * -1) . 'S'));
        }else{
            $this->add(new DateInterval('PT' . $seconds . 'S'));
        }
        return $this;
    }

    /**
     * @param bool $pad
     * @return int|string
     */
    public function getMinutes(bool $pad = false): int|string{
        return $pad ? $this->format('i') : intval($this->format('i'));
    }

    /**
     * @param int $minutes
     * @param bool $keep_time
     * @return Time
     */
    public function setMinutes(int $minutes, bool $keep_time = false): Time{
        if($keep_time)
            $this->setTime($this->getHours(), $minutes, $this->getSeconds(), $this->getMicroseconds());
        else
            $this->setTime($this->getHours(), $minutes);
        return $this;
    }

    /**
     * @param int $minutes
     * @return Time
     */
    public function addMinutes(int $minutes): self{
        if($minutes < 0){
            $this->sub(new DateInterval('PT' . ($minutes * -1) . 'M'));
        }else{
            $this->add(new DateInterval('PT' . $minutes . 'M'));
        }
        return $this;
    }

    /**
     * @param bool $twentyFourHour - 24- or 12-Hour-Format
     * @param bool $pad
     * @return int|string
     */
    public function getHours(bool $twentyFourHour = true, bool $pad = false): int|string{
        return $twentyFourHour ? ($pad ? $this->format('H') : intval($this->format('G'))) : ($pad ? $this->format('h') : intval($this->format('g')));
    }

    /**
     * @param int $hours
     * @param bool $keep_time
     * @return Time
     */
    public function setHours(int $hours, bool $keep_time = false): Time{
        if($keep_time)
            $this->setTime($hours, $this->getMinutes(), $this->getSeconds(), $this->getMicroseconds());
        else
            $this->setTime($hours, $this->getMinutes());
        return $this;
    }

    /**
     * @param int $hours
     * @return Time
     */
    public function addHours(int $hours): self{
        if($hours < 0){
            $this->sub(new DateInterval('PT' . ($hours * -1) . 'H'));
        }else{
            $this->add(new DateInterval('PT' . $hours . 'H'));
        }
        return $this;
    }

    /**
     * @param bool $pad
     * @return int|string
     */
    public function getDay(bool $pad = false): int|string{
        return $pad ? $this->format('d') : intval($this->format('j'));
    }

    /**
     * @param bool $pad
     * @return int|string
     */
    public function getDayOfWeek(bool $pad = false): int|string{
        $day = intval($this->format('w'));//0(So)-6(Sa);
        if($day === 0)
            $day = 7;
        return $pad ? Util::pad($day) : $day;
    }

    /**
     * @param bool $pad
     * @return int|string
     */
    public function getDayOfYear(bool $pad = false): int|string{
        $day = intval($this->format('z'));
        return $pad ? Util::pad($day) : $day;
    }

    /**
     * @param int $days
     * @return Time
     */
    public function addDays(int $days): self{
        if($days < 0){
            $this->sub(new DateInterval('P' . ($days * -1) . 'D'));
        }else{
            $this->add(new DateInterval('P' . $days . 'D'));
        }
        return $this;
    }

    /**
     * @param bool $pad
     * @return int|string
     */
    public function getWeek(bool $pad = false): int|string{
        $week = intval($this->format('W'));
        return $pad ? Util::pad($week) : $week;
    }

    /**
     * @param bool $pad
     * @return int|string
     */
    public function getMonth(bool $pad = false): int|string{
        return $pad ? $this->format('m') : intval($this->format('n'));
    }

    /**
     * @param bool $short
     * @return string
     */
    public function getMonthName(bool $short): string{
        return $short ? $this->format('M') : intval($this->format('F'));
    }

    /**
     * @return int
     */
    public function getDaysInMonth(): int{
        return intval($this->format('t'));
    }

    /**
     * @return int
     */
    public function getYear(): int{
        return intval($this->format('Y'));
    }

    /**
     * @return string
     */
    public function toString(): string{
        return $this->format(DATE_RFC3339_EXTENDED);
    }

    /**
     * @return string
     */
    public function jsonSerialize(): mixed{
        return $this->toString();
    }

}
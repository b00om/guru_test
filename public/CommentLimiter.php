<?php

class CommentLimiter
{
    private const int MAX_COMMENTS = 3;
    private const int PERIOD = 10;

    /**
     * @var array<int, list<int>>
     */
    private array $commentLogs = [];

    public function canPost(int $userId): bool
    {
        $now = time();
        $periodStart = $now - self::PERIOD;

        if (!isset($this->commentLogs[$userId])) {
            $this->commentLogs[$userId] = [];
        }

        $recentComments = [];

        foreach ($this->commentLogs[$userId] as $time) {
            if ($time > $periodStart) {
                $recentComments[] = $time;
            }
        }

        $this->commentLogs[$userId] = $recentComments;

        if (count($this->commentLogs[$userId]) >= self::MAX_COMMENTS) {
            return false;
        }

        $this->commentLogs[$userId][] = $now;

        return true;
    }
}
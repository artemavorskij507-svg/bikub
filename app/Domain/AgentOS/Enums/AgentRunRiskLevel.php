<?php

namespace App\Domain\AgentOS\Enums;

enum AgentRunRiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}

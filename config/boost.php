<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Agent Guidelines
    |--------------------------------------------------------------------------
    |
    | Boost writes one guidelines file per agent, which left two identical
    | copies here. Claude Code is pointed at the file Cursor already uses,
    | and CLAUDE.md imports it, so Boost only ever maintains AGENTS.md.
    |
    */

    'agents' => [
        'claude_code' => [
            'guidelines_path' => 'AGENTS.md',
        ],
    ],

];

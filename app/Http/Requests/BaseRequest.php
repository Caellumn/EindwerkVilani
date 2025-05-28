<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;

class BaseRequest extends Request
{
    /**
     * Determine if the current request is asking for JSON.
     *
     * @return bool
     */
    public function expectsJson()
    {
        // Always return JSON for API routes
        if ($this->is('api/*')) {
            return true;
        }

        // Fall back to the default behavior for non-API routes
        return parent::expectsJson();
    }
} 
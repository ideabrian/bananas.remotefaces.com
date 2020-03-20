<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscription extends Model
{        
    protected $hidden = ['created_at','user_id'];
}
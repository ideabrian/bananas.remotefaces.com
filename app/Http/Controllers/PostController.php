<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\User;
use  App\Post;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    
    public function create(Request $request)
    {
        $this->validate($request, [
            'content' => 'required|string'
        ]);

        $post = new Post;
        $post->content = $request['content'];

        //get tags, if any

        $post->user_id = Auth::user()->id;
        $post->save();

        return $post;
    }

    public function find(){

        $user_id = 0;
        if(Auth::user()){
            $user_id = Auth::user()->id;
        }

        $posts = Post::where('approved',1)->orWhere('user_id', $user_id)->orderBy('created_at','desc')->get();

        return $posts;
    }

    
}
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects
     */
    public function index(Request $request)
    {
        // Sample data matching your React frontend
        $projects = [
            [
                'id' => 1,
                'title' => 'PILLOW WALK',
                'client' => 'ALDO',
                'categories' => ['color grading'],
                'image' => '/assets/works/work1.jpg',
                'video_url' => 'https://videos.virtual-app.my.id/aldo_pillow_walk.mp4',
                'description' => 'Color grading project for ALDO footwear campaign',
                'year' => '2024'
            ],
            [
                'id' => 2,
                'title' => 'RAMADAN 2024',
                'client' => 'TOKOPEDIA',
                'categories' => ['color grading', 'vfx'],
                'image' => '/assets/works/work2.jpg',
                'video_url' => 'https://videos.virtual-app.my.id/tokpedia_ramadhan.mp4',
                'description' => 'Tokopedia Ramadan campaign with VFX and color grading',
                'year' => '2024'
            ],
            [
                'id' => 3,
                'title' => 'PROJECT_NAME',
                'client' => 'CLIENTS',
                'categories' => ['motion graphic', 'vfx', 'color grading', 'cgi'],
                'image' => '/assets/works/work3.jpg',
                'video_url' => null,
                'description' => 'Sample project description',
                'year' => '2024'
            ]
        ];

        // Filter by category if provided
        if ($request->has('category') && $request->category !== 'all project') {
            $projects = array_filter($projects, function($project) use ($request) {
                return in_array($request->category, $project['categories']);
            });
        }

        return response()->json([
            'success' => true,
            'data' => array_values($projects),
            'total' => count($projects)
        ]);
    }

    /**
     * Display a single project
     */
    public function show($id)
    {
        // In real implementation, you'd fetch from database
        $projects = $this->index(new Request())->getData(true)['data'];
        
        $project = collect($projects)->firstWhere('id', (int)$id);
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    /**
     * Get projects by category
     */
    public function getByCategory($category)
    {
        $request = new Request(['category' => $category]);
        return $this->index($request);
    }
}
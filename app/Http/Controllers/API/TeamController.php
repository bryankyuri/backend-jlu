<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of team members
     */
    public function index()
    {
        // Sample data matching your React frontend About page
        $team = [
            [
                'id' => 1,
                'name' => 'Dwi Agung Pambudi',
                'position' => 'Managing Director, Co-founder',
                'image' => '/assets/team/new/team_1.jpg',
                'bio' => 'Experienced managing director with extensive background in post-production.',
                'specialties' => ['Management', 'Strategy', 'Client Relations']
            ],
            [
                'id' => 2,
                'name' => 'Kenzo Miyake',
                'position' => 'Senior Colorist, Co-founder',
                'image' => '/assets/team/new/team_2.jpg',
                'bio' => 'Expert colorist with years of experience in color grading.',
                'specialties' => ['Color Grading', 'Color Science', 'Post-Production']
            ],
            [
                'id' => 3,
                'name' => 'Yuda Gustian',
                'position' => 'Executive Producer',
                'image' => '/assets/team/new/team_3.jpg',
                'bio' => 'Seasoned producer managing complex post-production workflows.',
                'specialties' => ['Production', 'Project Management', 'Workflow Optimization']
            ],
            [
                'id' => 4,
                'name' => 'Fian Firyanto',
                'position' => 'Finance',
                'image' => '/assets/team/new/team_4.jpg',
                'bio' => 'Financial expert ensuring smooth business operations.',
                'specialties' => ['Finance', 'Business Operations', 'Planning']
            ],
            [
                'id' => 5,
                'name' => 'M. Irvan Setiawan',
                'position' => 'Senior VFX artist, Compositor',
                'image' => '/assets/team/new/team_5.jpg',
                'bio' => 'Creative VFX artist specializing in compositing and visual effects.',
                'specialties' => ['VFX', 'Compositing', 'Motion Graphics']
            ],
            [
                'id' => 6,
                'name' => 'Alvin Rizkyadi',
                'position' => 'Junior Colorist',
                'image' => '/assets/team/new/team_6.jpg',
                'bio' => 'Talented colorist developing expertise in color grading.',
                'specialties' => ['Color Grading', 'Color Correction', 'Post-Production']
            ],
            [
                'id' => 7,
                'name' => 'Michael Thung',
                'position' => 'Junior VFX artist',
                'image' => '/assets/team/new/team_7.jpg',
                'bio' => 'Emerging VFX artist with passion for visual storytelling.',
                'specialties' => ['VFX', 'Animation', 'Creative Design']
            ],
            [
                'id' => 8,
                'name' => 'Irwan Syahrani',
                'position' => 'Post Producer',
                'image' => '/assets/team/new/team_8.jpg',
                'bio' => 'Experienced post producer coordinating post-production workflows.',
                'specialties' => ['Post-Production', 'Workflow Management', 'Quality Control']
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $team,
            'total' => count($team)
        ]);
    }

    /**
     * Display a single team member
     */
    public function show($id)
    {
        $team = $this->index()->getData(true)['data'];
        
        $member = collect($team)->firstWhere('id', (int)$id);
        
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Team member not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $member
        ]);
    }
}
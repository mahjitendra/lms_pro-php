<?php

/**
 * User Profile Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class UserProfile extends Model
{
    protected $table = 'user_profiles';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'user_id', 'bio', 'skills', 'interests', 'education', 'experience',
        'social_links', 'achievements', 'goals', 'learning_style',
        'availability', 'portfolio_url', 'resume_url'
    ];
    
    protected $casts = [
        'user_id' => 'integer',
        'skills' => 'json',
        'interests' => 'json',
        'education' => 'json',
        'experience' => 'json',
        'social_links' => 'json',
        'achievements' => 'json',
        'goals' => 'json',
        'availability' => 'json'
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->database->table('users')
            ->where('id', $this->user_id)
            ->first();
    }

    /**
     * Get formatted skills
     */
    public function getSkillsListAttribute()
    {
        if (!$this->skills) {
            return [];
        }
        
        $skills = json_decode($this->skills, true);
        return is_array($skills) ? $skills : [];
    }

    /**
     * Get formatted interests
     */
    public function getInterestsListAttribute()
    {
        if (!$this->interests) {
            return [];
        }
        
        $interests = json_decode($this->interests, true);
        return is_array($interests) ? $interests : [];
    }

    /**
     * Get social media links
     */
    public function getSocialLinksListAttribute()
    {
        if (!$this->social_links) {
            return [];
        }
        
        $links = json_decode($this->social_links, true);
        return is_array($links) ? $links : [];
    }

    /**
     * Add skill
     */
    public function addSkill($skill, $level = null)
    {
        $skills = $this->getSkillsListAttribute();
        
        $skillData = [
            'name' => $skill,
            'level' => $level,
            'added_at' => date('Y-m-d H:i:s')
        ];
        
        $skills[] = $skillData;
        
        return $this->database->update('user_profiles', [
            'skills' => json_encode($skills),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Remove skill
     */
    public function removeSkill($skillName)
    {
        $skills = $this->getSkillsListAttribute();
        
        $skills = array_filter($skills, function($skill) use ($skillName) {
            return $skill['name'] !== $skillName;
        });
        
        return $this->database->update('user_profiles', [
            'skills' => json_encode(array_values($skills)),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Update social link
     */
    public function updateSocialLink($platform, $url)
    {
        $links = $this->getSocialLinksListAttribute();
        $links[$platform] = $url;
        
        return $this->database->update('user_profiles', [
            'social_links' => json_encode($links),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Get profile completion percentage
     */
    public function getCompletionPercentage()
    {
        $fields = [
            'bio', 'skills', 'interests', 'education', 'experience',
            'goals', 'learning_style'
        ];
        
        $completed = 0;
        $total = count($fields);
        
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }
        
        return round(($completed / $total) * 100);
    }

    /**
     * Find profile by user ID
     */
    public static function findByUserId($userId)
    {
        $database = App::getInstance()->getDatabase();
        $profileData = $database->table('user_profiles')
            ->where('user_id', $userId)
            ->first();
            
        if ($profileData) {
            $profile = new self($database);
            $profile->fill($profileData);
            return $profile;
        }
        
        return null;
    }

    /**
     * Create profile for user
     */
    public static function createForUser($userId, $data = [])
    {
        $database = App::getInstance()->getDatabase();
        
        $profileData = array_merge([
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], $data);
        
        $id = $database->insert('user_profiles', $profileData);
        
        if ($id) {
            $profile = new self($database);
            $profileData['id'] = $id;
            $profile->fill($profileData);
            return $profile;
        }
        
        return null;
    }
}
<?php

/**
 * Category Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'name', 'slug', 'description', 'parent_id', 'image', 'icon',
        'color', 'sort_order', 'is_featured', 'status'
    ];
    
    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'status' => 'integer'
    ];

    /**
     * Boot the model
     */
    protected function initialize()
    {
        // Generate slug from name if not provided
        $this->on('creating', function($data) {
            if (!isset($data['slug']) && isset($data['name'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            }
            
            if (!isset($data['status'])) {
                $data['status'] = 1; // Active by default
            }
            
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = $this->getNextSortOrder($data['parent_id'] ?? null);
            }
        });
        
        $this->on('updating', function($data) {
            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $this->id);
            }
        });
    }

    /**
     * Generate unique slug
     */
    private function generateUniqueSlug($name, $excludeId = null)
    {
        $baseSlug = Helper::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if slug exists
     */
    private function slugExists($slug, $excludeId = null)
    {
        $query = $this->database->table('categories')->where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Get next sort order
     */
    private function getNextSortOrder($parentId = null)
    {
        $query = $this->database->table('categories');
        
        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }
        
        $result = $query->selectRaw('MAX(sort_order) as max_order')->first();
        
        return $result && $result['max_order'] ? $result['max_order'] + 1 : 1;
    }

    /**
     * Get parent category
     */
    public function parent()
    {
        if (!$this->parent_id) {
            return null;
        }
        
        return $this->database->table('categories')
            ->where('id', $this->parent_id)
            ->first();
    }

    /**
     * Get child categories
     */
    public function children()
    {
        return $this->database->table('categories')
            ->where('parent_id', $this->id)
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants()
    {
        $descendants = [];
        $children = $this->children();
        
        foreach ($children as $child) {
            $descendants[] = $child;
            $childCategory = new self($this->database);
            $childCategory->fill($child);
            $descendants = array_merge($descendants, $childCategory->descendants());
        }
        
        return $descendants;
    }

    /**
     * Get category path (breadcrumb)
     */
    public function getPath()
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug
            ]);
            
            if ($current->parent_id) {
                $parentData = $this->database->table('categories')
                    ->where('id', $current->parent_id)
                    ->first();
                    
                if ($parentData) {
                    $current = new self($this->database);
                    $current->fill($parentData);
                } else {
                    $current = null;
                }
            } else {
                $current = null;
            }
        }
        
        return $path;
    }

    /**
     * Get courses in this category
     */
    public function courses($includeChildren = false)
    {
        $query = $this->database->table('courses c')
            ->leftJoin('users u', 'c.instructor_id = u.id')
            ->where('c.status', COURSE_STATUS_PUBLISHED)
            ->select([
                'c.*',
                'u.first_name as instructor_first_name',
                'u.last_name as instructor_last_name'
            ]);
            
        if ($includeChildren) {
            $categoryIds = [$this->id];
            $descendants = $this->descendants();
            
            foreach ($descendants as $descendant) {
                $categoryIds[] = $descendant['id'];
            }
            
            $query->whereIn('c.category_id', $categoryIds);
        } else {
            $query->where('c.category_id', $this->id);
        }
        
        return $query->orderBy('c.created_at', 'DESC')->get();
    }

    /**
     * Get courses count
     */
    public function getCoursesCount($includeChildren = false)
    {
        $query = $this->database->table('courses')
            ->where('status', COURSE_STATUS_PUBLISHED);
            
        if ($includeChildren) {
            $categoryIds = [$this->id];
            $descendants = $this->descendants();
            
            foreach ($descendants as $descendant) {
                $categoryIds[] = $descendant['id'];
            }
            
            $query->whereIn('category_id', $categoryIds);
        } else {
            $query->where('category_id', $this->id);
        }
        
        return $query->count();
    }

    /**
     * Check if category is active
     */
    public function isActive()
    {
        return $this->status === 1;
    }

    /**
     * Check if category has children
     */
    public function hasChildren()
    {
        return $this->database->table('categories')
            ->where('parent_id', $this->id)
            ->where('status', 1)
            ->exists();
    }

    /**
     * Check if category can be deleted
     */
    public function canBeDeleted()
    {
        // Check if has courses
        $hasCourses = $this->database->table('courses')
            ->where('category_id', $this->id)
            ->exists();
            
        if ($hasCourses) {
            return false;
        }
        
        // Check if has children
        if ($this->hasChildren()) {
            return false;
        }
        
        return true;
    }

    /**
     * Get category tree
     */
    public static function getTree($parentId = null, $maxDepth = null, $currentDepth = 0)
    {
        $database = App::getInstance()->getDatabase();
        
        if ($maxDepth !== null && $currentDepth >= $maxDepth) {
            return [];
        }
        
        $query = $database->table('categories')
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC');
            
        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }
        
        $categories = $query->get();
        $tree = [];
        
        foreach ($categories as $category) {
            $categoryData = $category;
            $categoryData['children'] = self::getTree($category['id'], $maxDepth, $currentDepth + 1);
            $categoryData['courses_count'] = $database->table('courses')
                ->where('category_id', $category['id'])
                ->where('status', COURSE_STATUS_PUBLISHED)
                ->count();
            $tree[] = $categoryData;
        }
        
        return $tree;
    }

    /**
     * Get flat list of categories
     */
    public static function getFlat($parentId = null, $prefix = '')
    {
        $database = App::getInstance()->getDatabase();
        
        $query = $database->table('categories')
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC');
            
        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }
        
        $categories = $query->get();
        $flat = [];
        
        foreach ($categories as $category) {
            $categoryData = $category;
            $categoryData['display_name'] = $prefix . $category['name'];
            $flat[] = $categoryData;
            
            // Get children recursively
            $children = self::getFlat($category['id'], $prefix . '— ');
            $flat = array_merge($flat, $children);
        }
        
        return $flat;
    }

    /**
     * Get categories for select dropdown
     */
    public static function getForSelect($includeEmpty = true)
    {
        $categories = self::getFlat();
        $options = [];
        
        if ($includeEmpty) {
            $options[''] = 'Select Category';
        }
        
        foreach ($categories as $category) {
            $options[$category['id']] = $category['display_name'];
        }
        
        return $options;
    }

    /**
     * Get featured categories
     */
    public static function getFeatured($limit = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('categories')
            ->where('is_featured', 1)
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        $categories = $query->get();
        
        // Add courses count for each category
        foreach ($categories as &$category) {
            $category['courses_count'] = $database->table('courses')
                ->where('category_id', $category['id'])
                ->where('status', COURSE_STATUS_PUBLISHED)
                ->count();
        }
        
        return $categories;
    }

    /**
     * Get popular categories (by course count)
     */
    public static function getPopular($limit = 10)
    {
        $database = App::getInstance()->getDatabase();
        return $database->table('categories cat')
            ->leftJoin('courses c', 'cat.id = c.category_id')
            ->where('cat.status', 1)
            ->where('c.status', COURSE_STATUS_PUBLISHED)
            ->select(['cat.*'])
            ->selectRaw('COUNT(c.id) as courses_count')
            ->groupBy('cat.id')
            ->having('courses_count', '>', 0)
            ->orderBy('courses_count', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Search categories
     */
    public static function search($query)
    {
        $database = App::getInstance()->getDatabase();
        return $database->table('categories')
            ->where('status', 1)
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Reorder categories
     */
    public static function reorder($categoryIds, $parentId = null)
    {
        $database = App::getInstance()->getDatabase();
        
        foreach ($categoryIds as $index => $categoryId) {
            $database->update('categories', [
                'sort_order' => $index + 1,
                'parent_id' => $parentId,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $categoryId]);
        }
        
        return true;
    }

    /**
     * Move category to different parent
     */
    public function moveTo($newParentId)
    {
        // Prevent moving to own descendant
        if ($newParentId) {
            $descendants = $this->descendants();
            foreach ($descendants as $descendant) {
                if ($descendant['id'] == $newParentId) {
                    return false;
                }
            }
        }
        
        $sortOrder = $this->getNextSortOrder($newParentId);
        
        return $this->database->update('categories', [
            'parent_id' => $newParentId,
            'sort_order' => $sortOrder,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Find category by slug
     */
    public static function findBySlug($slug)
    {
        $database = App::getInstance()->getDatabase();
        $categoryData = $database->table('categories')
            ->where('slug', $slug)
            ->where('status', 1)
            ->first();
            
        if ($categoryData) {
            $category = new self($database);
            $category->fill($categoryData);
            return $category;
        }
        
        return null;
    }
}
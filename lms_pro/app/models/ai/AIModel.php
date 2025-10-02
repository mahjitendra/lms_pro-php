<?php

/**
 * AI Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class AIModel extends Model
{
    protected $table = 'ai_models';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'name', 'slug', 'description', 'type', 'framework', 'version',
        'file_path', 'file_size', 'accuracy', 'parameters', 'training_data',
        'created_by', 'status', 'is_public', 'download_count', 'metadata'
    ];
    
    protected $casts = [
        'file_size' => 'integer',
        'accuracy' => 'float',
        'parameters' => 'json',
        'training_data' => 'json',
        'created_by' => 'integer',
        'status' => 'integer',
        'is_public' => 'boolean',
        'download_count' => 'integer',
        'metadata' => 'json'
    ];

    /**
     * Boot the model
     */
    protected function initialize()
    {
        $this->on('creating', function($data) {
            if (!isset($data['slug']) && isset($data['name'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            }
            
            if (!isset($data['status'])) {
                $data['status'] = AI_MODEL_STATUS_TRAINING;
            }
            
            if (!isset($data['version'])) {
                $data['version'] = '1.0.0';
            }
        });
    }

    /**
     * Generate unique slug
     */
    private function generateUniqueSlug($name)
    {
        $baseSlug = Helper::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if slug exists
     */
    private function slugExists($slug)
    {
        return $this->database->table('ai_models')
            ->where('slug', $slug)
            ->exists();
    }

    /**
     * Get model creator
     */
    public function creator()
    {
        return $this->database->table('users')
            ->where('id', $this->created_by)
            ->first();
    }

    /**
     * Get model training jobs
     */
    public function trainingJobs()
    {
        return $this->database->table('training_jobs')
            ->where('model_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Get model predictions
     */
    public function predictions($limit = 100)
    {
        return $this->database->table('predictions')
            ->where('model_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get model experiments
     */
    public function experiments()
    {
        return $this->database->table('ml_experiments')
            ->where('model_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Check if model is trained
     */
    public function isTrained()
    {
        return $this->status === AI_MODEL_STATUS_TRAINED;
    }

    /**
     * Check if model is deployed
     */
    public function isDeployed()
    {
        return $this->status === AI_MODEL_STATUS_DEPLOYED;
    }

    /**
     * Check if model is public
     */
    public function isPublic()
    {
        return $this->is_public;
    }

    /**
     * Get model file path
     */
    public function getFilePath()
    {
        if (!$this->file_path) {
            return null;
        }
        
        return STORAGE_PATH . '/ai-models/' . $this->file_path;
    }

    /**
     * Get model download URL
     */
    public function getDownloadUrl()
    {
        if (!$this->file_path) {
            return null;
        }
        
        return BASE_URL . '/api/v1/ai/models/' . $this->id . '/download';
    }

    /**
     * Get model metadata
     */
    public function getMetadata($key = null)
    {
        if (!$this->metadata) {
            return $key ? null : [];
        }
        
        $metadata = json_decode($this->metadata, true);
        
        if ($key) {
            return Helper::arrayGet($metadata, $key);
        }
        
        return $metadata;
    }

    /**
     * Update model metadata
     */
    public function updateMetadata($key, $value = null)
    {
        $metadata = $this->getMetadata();
        
        if (is_array($key)) {
            $metadata = array_merge($metadata, $key);
        } else {
            Helper::arraySet($metadata, $key, $value);
        }
        
        return $this->database->update('ai_models', [
            'metadata' => json_encode($metadata),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Train model
     */
    public function train($datasetId, $parameters = [])
    {
        // Create training job
        $jobData = [
            'model_id' => $this->id,
            'dataset_id' => $datasetId,
            'parameters' => json_encode($parameters),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $jobId = $this->database->insert('training_jobs', $jobData);
        
        // Update model status
        $this->database->update('ai_models', [
            'status' => AI_MODEL_STATUS_TRAINING,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
        
        // Here you would typically queue the training job
        // For now, we'll just return the job ID
        return $jobId;
    }

    /**
     * Make prediction
     */
    public function predict($inputData, $userId = null)
    {
        if (!$this->isTrained()) {
            throw new Exception('Model is not trained yet');
        }
        
        // Create prediction record
        $predictionData = [
            'model_id' => $this->id,
            'user_id' => $userId,
            'input_data' => json_encode($inputData),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $predictionId = $this->database->insert('predictions', $predictionData);
        
        // Here you would typically call the actual ML model
        // For now, we'll simulate a prediction
        $result = $this->simulatePrediction($inputData);
        
        // Update prediction with result
        $this->database->update('predictions', [
            'output_data' => json_encode($result),
            'confidence' => $result['confidence'] ?? 0.5,
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $predictionId]);
        
        return [
            'prediction_id' => $predictionId,
            'result' => $result
        ];
    }

    /**
     * Simulate prediction (placeholder)
     */
    private function simulatePrediction($inputData)
    {
        // This is a placeholder - in real implementation,
        // you would call your ML model here
        
        switch ($this->type) {
            case AI_MODEL_TYPE_CLASSIFICATION:
                return [
                    'class' => 'positive',
                    'confidence' => 0.85,
                    'probabilities' => [
                        'positive' => 0.85,
                        'negative' => 0.15
                    ]
                ];
                
            case AI_MODEL_TYPE_REGRESSION:
                return [
                    'value' => 42.5,
                    'confidence' => 0.92
                ];
                
            case AI_MODEL_TYPE_CLUSTERING:
                return [
                    'cluster' => 2,
                    'distance' => 0.3,
                    'confidence' => 0.78
                ];
                
            default:
                return [
                    'result' => 'unknown',
                    'confidence' => 0.5
                ];
        }
    }

    /**
     * Deploy model
     */
    public function deploy($endpoint = null)
    {
        if (!$this->isTrained()) {
            throw new Exception('Model must be trained before deployment');
        }
        
        // Update model status
        $this->database->update('ai_models', [
            'status' => AI_MODEL_STATUS_DEPLOYED,
            'deployed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
        
        // Update metadata with endpoint info
        if ($endpoint) {
            $this->updateMetadata('deployment.endpoint', $endpoint);
        }
        
        return true;
    }

    /**
     * Undeploy model
     */
    public function undeploy()
    {
        $this->database->update('ai_models', [
            'status' => AI_MODEL_STATUS_TRAINED,
            'deployed_at' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
        
        // Remove deployment info from metadata
        $metadata = $this->getMetadata();
        unset($metadata['deployment']);
        
        $this->database->update('ai_models', [
            'metadata' => json_encode($metadata)
        ], 'id = :id', ['id' => $this->id]);
        
        return true;
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount()
    {
        return $this->database->update('ai_models', [
            'download_count' => $this->download_count + 1,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Get model performance metrics
     */
    public function getPerformanceMetrics()
    {
        $metrics = [];
        
        // Get latest training job metrics
        $latestJob = $this->database->table('training_jobs')
            ->where('model_id', $this->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'DESC')
            ->first();
            
        if ($latestJob && $latestJob['metrics']) {
            $metrics = json_decode($latestJob['metrics'], true);
        }
        
        // Add prediction statistics
        $predictionStats = $this->database->table('predictions')
            ->where('model_id', $this->id)
            ->where('status', 'completed')
            ->selectRaw('
                COUNT(*) as total_predictions,
                AVG(confidence) as avg_confidence,
                MIN(confidence) as min_confidence,
                MAX(confidence) as max_confidence
            ')
            ->first();
            
        if ($predictionStats) {
            $metrics['predictions'] = [
                'total' => (int)$predictionStats['total_predictions'],
                'avg_confidence' => round($predictionStats['avg_confidence'], 3),
                'min_confidence' => round($predictionStats['min_confidence'], 3),
                'max_confidence' => round($predictionStats['max_confidence'], 3)
            ];
        }
        
        return $metrics;
    }

    /**
     * Create model version
     */
    public function createVersion($versionNumber, $changes = [])
    {
        return $this->database->insert('model_versions', [
            'model_id' => $this->id,
            'version' => $versionNumber,
            'changes' => json_encode($changes),
            'file_path' => $this->file_path,
            'accuracy' => $this->accuracy,
            'parameters' => $this->parameters,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get model versions
     */
    public function getVersions()
    {
        return $this->database->table('model_versions')
            ->where('model_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Search models
     */
    public static function search($query, $filters = [])
    {
        $database = App::getInstance()->getDatabase();
        $queryBuilder = $database->table('ai_models m')
            ->leftJoin('users u', 'm.created_by = u.id')
            ->select([
                'm.*',
                'u.first_name as creator_first_name',
                'u.last_name as creator_last_name'
            ]);
        
        // Search in name and description
        if ($query) {
            $queryBuilder->where(function($q) use ($query) {
                $q->where('m.name', 'LIKE', "%{$query}%")
                  ->orWhere('m.description', 'LIKE', "%{$query}%");
            });
        }
        
        // Apply filters
        if (isset($filters['type'])) {
            $queryBuilder->where('m.type', $filters['type']);
        }
        
        if (isset($filters['framework'])) {
            $queryBuilder->where('m.framework', $filters['framework']);
        }
        
        if (isset($filters['status'])) {
            $queryBuilder->where('m.status', $filters['status']);
        }
        
        if (isset($filters['is_public'])) {
            $queryBuilder->where('m.is_public', $filters['is_public']);
        }
        
        if (isset($filters['created_by'])) {
            $queryBuilder->where('m.created_by', $filters['created_by']);
        }
        
        return $queryBuilder->orderBy('m.created_at', 'DESC')->get();
    }

    /**
     * Get popular models
     */
    public static function getPopular($limit = 10)
    {
        $database = App::getInstance()->getDatabase();
        return $database->table('ai_models m')
            ->leftJoin('users u', 'm.created_by = u.id')
            ->where('m.is_public', 1)
            ->where('m.status', AI_MODEL_STATUS_TRAINED)
            ->select([
                'm.*',
                'u.first_name as creator_first_name',
                'u.last_name as creator_last_name'
            ])
            ->orderBy('m.download_count', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent models
     */
    public static function getRecent($limit = 10)
    {
        $database = App::getInstance()->getDatabase();
        return $database->table('ai_models m')
            ->leftJoin('users u', 'm.created_by = u.id')
            ->where('m.is_public', 1)
            ->where('m.status', AI_MODEL_STATUS_TRAINED)
            ->select([
                'm.*',
                'u.first_name as creator_first_name',
                'u.last_name as creator_last_name'
            ])
            ->orderBy('m.created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Find model by slug
     */
    public static function findBySlug($slug)
    {
        $database = App::getInstance()->getDatabase();
        $modelData = $database->table('ai_models')
            ->where('slug', $slug)
            ->first();
            
        if ($modelData) {
            $model = new self($database);
            $model->fill($modelData);
            return $model;
        }
        
        return null;
    }
}
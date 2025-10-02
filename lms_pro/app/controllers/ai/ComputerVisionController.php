<?php

/**
 * Computer Vision Controller
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Controller.php';

class ComputerVisionController extends Controller
{
    protected $middleware = ['auth'];
    private $cvLibrary;

    protected function initialize()
    {
        $this->requirePermission('access_ai_tools');
        $this->cvLibrary = $this->loadLibrary('ai/ComputerVision');
    }

    /**
     * Show computer vision dashboard
     */
    public function index()
    {
        $data = [
            'title' => 'Computer Vision - AI Tools',
            'features' => $this->getAvailableFeatures(),
            'recent_analyses' => $this->getRecentAnalyses(),
            'usage_stats' => $this->getUsageStats()
        ];
        
        return $this->view('ai/computer-vision/index', $data, 'student');
    }

    /**
     * Analyze image
     */
    public function analyzeImage()
    {
        if (!$this->request->isPost()) {
            return $this->abort(405);
        }
        
        // Validate file upload
        if (!$this->hasFile('image')) {
            return $this->error('No image file provided', [], 400);
        }
        
        $file = $this->file('image');
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            return $this->error('Invalid image format. Allowed: ' . implode(', ', $allowedTypes), [], 400);
        }
        
        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return $this->error('Image file too large. Maximum size: 10MB', [], 400);
        }
        
        try {
            // Upload file
            $uploadResult = $this->uploadFile($file, 'ai/images', $allowedTypes);
            
            // Perform analysis
            $analysisType = $this->input('analysis_type', 'general');
            $result = $this->performImageAnalysis($uploadResult['path'], $analysisType);
            
            // Save analysis result
            $analysisId = $this->saveAnalysisResult('image_analysis', [
                'file_path' => $uploadResult['path'],
                'file_url' => $uploadResult['url'],
                'analysis_type' => $analysisType,
                'result' => $result
            ]);
            
            return $this->success('Image analysis completed', [
                'analysis_id' => $analysisId,
                'result' => $result,
                'image_url' => $uploadResult['url']
            ]);
            
        } catch (Exception $e) {
            $this->log('Image analysis error: ' . $e->getMessage(), 'error');
            return $this->error('Image analysis failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Analyze video
     */
    public function analyzeVideo()
    {
        if (!$this->request->isPost()) {
            return $this->abort(405);
        }
        
        // Validate file upload
        if (!$this->hasFile('video')) {
            return $this->error('No video file provided', [], 400);
        }
        
        $file = $this->file('video');
        
        // Validate file type
        $allowedTypes = ['mp4', 'avi', 'mov', 'wmv'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            return $this->error('Invalid video format. Allowed: ' . implode(', ', $allowedTypes), [], 400);
        }
        
        // Validate file size (max 100MB)
        if ($file['size'] > 100 * 1024 * 1024) {
            return $this->error('Video file too large. Maximum size: 100MB', [], 400);
        }
        
        try {
            // Upload file
            $uploadResult = $this->uploadFile($file, 'ai/videos', $allowedTypes);
            
            // Perform analysis
            $analysisType = $this->input('analysis_type', 'object_detection');
            $result = $this->performVideoAnalysis($uploadResult['path'], $analysisType);
            
            // Save analysis result
            $analysisId = $this->saveAnalysisResult('video_analysis', [
                'file_path' => $uploadResult['path'],
                'file_url' => $uploadResult['url'],
                'analysis_type' => $analysisType,
                'result' => $result
            ]);
            
            return $this->success('Video analysis completed', [
                'analysis_id' => $analysisId,
                'result' => $result,
                'video_url' => $uploadResult['url']
            ]);
            
        } catch (Exception $e) {
            $this->log('Video analysis error: ' . $e->getMessage(), 'error');
            return $this->error('Video analysis failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Face recognition
     */
    public function faceRecognition()
    {
        if (!$this->request->isPost()) {
            return $this->abort(405);
        }
        
        // Validate file upload
        if (!$this->hasFile('image')) {
            return $this->error('No image file provided', [], 400);
        }
        
        $file = $this->file('image');
        
        try {
            // Upload file
            $uploadResult = $this->uploadFile($file, 'ai/faces', ['jpg', 'jpeg', 'png']);
            
            // Perform face recognition
            $result = $this->cvLibrary->recognizeFaces($uploadResult['path']);
            
            // Save analysis result
            $analysisId = $this->saveAnalysisResult('face_recognition', [
                'file_path' => $uploadResult['path'],
                'file_url' => $uploadResult['url'],
                'result' => $result
            ]);
            
            return $this->success('Face recognition completed', [
                'analysis_id' => $analysisId,
                'result' => $result,
                'image_url' => $uploadResult['url']
            ]);
            
        } catch (Exception $e) {
            $this->log('Face recognition error: ' . $e->getMessage(), 'error');
            return $this->error('Face recognition failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * OCR (Optical Character Recognition)
     */
    public function ocr()
    {
        if (!$this->request->isPost()) {
            return $this->abort(405);
        }
        
        // Validate file upload
        if (!$this->hasFile('image')) {
            return $this->error('No image file provided', [], 400);
        }
        
        $file = $this->file('image');
        $language = $this->input('language', 'eng');
        
        try {
            // Upload file
            $uploadResult = $this->uploadFile($file, 'ai/ocr', ['jpg', 'jpeg', 'png', 'pdf']);
            
            // Perform OCR
            $result = $this->cvLibrary->performOCR($uploadResult['path'], $language);
            
            // Save analysis result
            $analysisId = $this->saveAnalysisResult('ocr', [
                'file_path' => $uploadResult['path'],
                'file_url' => $uploadResult['url'],
                'language' => $language,
                'result' => $result
            ]);
            
            return $this->success('OCR completed', [
                'analysis_id' => $analysisId,
                'result' => $result,
                'image_url' => $uploadResult['url']
            ]);
            
        } catch (Exception $e) {
            $this->log('OCR error: ' . $e->getMessage(), 'error');
            return $this->error('OCR failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Perform image analysis
     */
    private function performImageAnalysis($imagePath, $analysisType)
    {
        switch ($analysisType) {
            case 'object_detection':
                return $this->cvLibrary->detectObjects($imagePath);
                
            case 'image_classification':
                return $this->cvLibrary->classifyImage($imagePath);
                
            case 'face_detection':
                return $this->cvLibrary->detectFaces($imagePath);
                
            case 'edge_detection':
                return $this->cvLibrary->detectEdges($imagePath);
                
            case 'color_analysis':
                return $this->cvLibrary->analyzeColors($imagePath);
                
            default:
                return $this->cvLibrary->generalAnalysis($imagePath);
        }
    }

    /**
     * Perform video analysis
     */
    private function performVideoAnalysis($videoPath, $analysisType)
    {
        switch ($analysisType) {
            case 'object_tracking':
                return $this->cvLibrary->trackObjects($videoPath);
                
            case 'motion_detection':
                return $this->cvLibrary->detectMotion($videoPath);
                
            case 'scene_detection':
                return $this->cvLibrary->detectScenes($videoPath);
                
            case 'face_tracking':
                return $this->cvLibrary->trackFaces($videoPath);
                
            default:
                return $this->cvLibrary->analyzeVideo($videoPath);
        }
    }

    /**
     * Save analysis result
     */
    private function saveAnalysisResult($type, $data)
    {
        return $this->database->insert('cv_image_analysis', [
            'user_id' => $this->userId(),
            'analysis_type' => $type,
            'file_path' => $data['file_path'],
            'file_url' => $data['file_url'],
            'parameters' => json_encode($data),
            'result' => json_encode($data['result']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get available features
     */
    private function getAvailableFeatures()
    {
        $config = App::getInstance()->getConfig('ai.computer_vision');
        
        return [
            'face_detection' => $config['features']['face_detection'] ?? false,
            'face_recognition' => $config['features']['face_recognition'] ?? false,
            'object_detection' => $config['features']['object_detection'] ?? false,
            'image_classification' => $config['features']['image_classification'] ?? false,
            'ocr' => $config['features']['ocr'] ?? false,
            'image_enhancement' => $config['features']['image_enhancement'] ?? false,
            'video_analysis' => $config['features']['video_analysis'] ?? false
        ];
    }

    /**
     * Get recent analyses
     */
    private function getRecentAnalyses($limit = 10)
    {
        return $this->database->table('cv_image_analysis')
            ->where('user_id', $this->userId())
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get usage statistics
     */
    private function getUsageStats()
    {
        $userId = $this->userId();
        
        return [
            'total_analyses' => $this->database->table('cv_image_analysis')
                ->where('user_id', $userId)
                ->count(),
                
            'this_month' => $this->database->table('cv_image_analysis')
                ->where('user_id', $userId)
                ->where('created_at', '>=', date('Y-m-01'))
                ->count(),
                
            'by_type' => $this->database->table('cv_image_analysis')
                ->where('user_id', $userId)
                ->selectRaw('analysis_type, COUNT(*) as count')
                ->groupBy('analysis_type')
                ->get()
        ];
    }

    /**
     * Get analysis history
     */
    public function getHistory()
    {
        $page = (int)$this->query('page', 1);
        $perPage = (int)$this->query('per_page', 20);
        
        $query = $this->database->table('cv_image_analysis')
            ->where('user_id', $this->userId());
            
        $results = $this->paginate($query, $perPage, $page);
        
        if ($this->request->isAjax()) {
            return $this->json($results);
        }
        
        $data = [
            'title' => 'Computer Vision History',
            'analyses' => $results['data'],
            'pagination' => $results['pagination']
        ];
        
        return $this->view('ai/computer-vision/history', $data, 'student');
    }

    /**
     * Download analysis result
     */
    public function downloadResult($id)
    {
        $analysis = $this->database->table('cv_image_analysis')
            ->where('id', $id)
            ->where('user_id', $this->userId())
            ->first();
            
        if (!$analysis) {
            return $this->abort(404);
        }
        
        $result = json_decode($analysis['result'], true);
        $filename = 'cv_analysis_' . $id . '_' . date('Y-m-d') . '.json';
        
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return json_encode($result, JSON_PRETTY_PRINT);
    }
}
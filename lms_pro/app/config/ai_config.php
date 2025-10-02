<?php

/**
 * AI/ML Configuration
 * LMS Pro - Learning Management System
 */

defined('BASEPATH') OR exit('No direct script access allowed');

return [
    // TensorFlow Configuration
    'tensorflow' => [
        'enabled' => $_ENV['TENSORFLOW_ENABLED'] ?? true,
        'model_path' => STORAGE_PATH . '/ai-models',
        'python_path' => $_ENV['PYTHON_PATH'] ?? '/usr/bin/python3',
        'api_url' => $_ENV['TENSORFLOW_API_URL'] ?? 'http://localhost:8501',
        'timeout' => $_ENV['TENSORFLOW_TIMEOUT'] ?? 30,
        'memory_limit' => $_ENV['TENSORFLOW_MEMORY_LIMIT'] ?? '2G',
        'gpu_enabled' => $_ENV['TENSORFLOW_GPU_ENABLED'] ?? false,
        'models' => [
            'image_classification' => [
                'path' => 'trained/image_classification.h5',
                'input_shape' => [224, 224, 3],
                'classes' => ['cat', 'dog', 'bird', 'car', 'person'],
            ],
            'text_classification' => [
                'path' => 'trained/text_classification.h5',
                'max_length' => 512,
                'vocab_size' => 10000,
            ],
            'recommendation' => [
                'path' => 'trained/recommendation.h5',
                'embedding_dim' => 128,
                'num_factors' => 50,
            ],
        ],
    ],

    // OpenCV Configuration
    'opencv' => [
        'enabled' => $_ENV['OPENCV_ENABLED'] ?? true,
        'library_path' => $_ENV['OPENCV_PATH'] ?? '/usr/local/lib',
        'cascade_path' => $_ENV['OPENCV_CASCADE_PATH'] ?? '/usr/share/opencv4/haarcascades',
        'face_detection' => [
            'cascade_file' => 'haarcascade_frontalface_default.xml',
            'scale_factor' => 1.1,
            'min_neighbors' => 5,
            'min_size' => [30, 30],
        ],
        'object_detection' => [
            'config_file' => 'yolo.cfg',
            'weights_file' => 'yolo.weights',
            'classes_file' => 'coco.names',
            'confidence_threshold' => 0.5,
            'nms_threshold' => 0.4,
        ],
    ],

    // OpenAI Configuration
    'openai' => [
        'enabled' => $_ENV['OPENAI_ENABLED'] ?? false,
        'api_key' => $_ENV['OPENAI_API_KEY'] ?? null,
        'organization' => $_ENV['OPENAI_ORGANIZATION'] ?? null,
        'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo',
        'max_tokens' => $_ENV['OPENAI_MAX_TOKENS'] ?? 1000,
        'temperature' => $_ENV['OPENAI_TEMPERATURE'] ?? 0.7,
        'timeout' => $_ENV['OPENAI_TIMEOUT'] ?? 30,
        'models' => [
            'chat' => 'gpt-3.5-turbo',
            'completion' => 'text-davinci-003',
            'embedding' => 'text-embedding-ada-002',
            'image' => 'dall-e-2',
            'audio' => 'whisper-1',
        ],
    ],

    // Hugging Face Configuration
    'huggingface' => [
        'enabled' => $_ENV['HUGGINGFACE_ENABLED'] ?? false,
        'api_key' => $_ENV['HUGGINGFACE_API_KEY'] ?? null,
        'api_url' => 'https://api-inference.huggingface.co/models',
        'timeout' => $_ENV['HUGGINGFACE_TIMEOUT'] ?? 30,
        'models' => [
            'sentiment' => 'cardiffnlp/twitter-roberta-base-sentiment-latest',
            'summarization' => 'facebook/bart-large-cnn',
            'translation' => 'Helsinki-NLP/opus-mt-en-fr',
            'question_answering' => 'deepset/roberta-base-squad2',
            'text_generation' => 'gpt2',
            'image_classification' => 'google/vit-base-patch16-224',
        ],
    ],

    // Scikit-learn Configuration
    'sklearn' => [
        'enabled' => $_ENV['SKLEARN_ENABLED'] ?? true,
        'algorithms' => [
            'classification' => [
                'random_forest' => [
                    'n_estimators' => 100,
                    'max_depth' => 10,
                    'random_state' => 42,
                ],
                'svm' => [
                    'kernel' => 'rbf',
                    'C' => 1.0,
                    'gamma' => 'scale',
                ],
                'logistic_regression' => [
                    'max_iter' => 1000,
                    'random_state' => 42,
                ],
            ],
            'regression' => [
                'linear_regression' => [],
                'random_forest' => [
                    'n_estimators' => 100,
                    'max_depth' => 10,
                    'random_state' => 42,
                ],
                'gradient_boosting' => [
                    'n_estimators' => 100,
                    'learning_rate' => 0.1,
                    'max_depth' => 3,
                ],
            ],
            'clustering' => [
                'kmeans' => [
                    'n_clusters' => 3,
                    'random_state' => 42,
                ],
                'dbscan' => [
                    'eps' => 0.5,
                    'min_samples' => 5,
                ],
                'hierarchical' => [
                    'n_clusters' => 3,
                    'linkage' => 'ward',
                ],
            ],
        ],
    ],

    // Natural Language Processing
    'nlp' => [
        'enabled' => $_ENV['NLP_ENABLED'] ?? true,
        'libraries' => [
            'spacy' => [
                'model' => 'en_core_web_sm',
                'disable' => ['parser', 'ner'],
            ],
            'nltk' => [
                'data_path' => STORAGE_PATH . '/nltk_data',
                'punkt' => true,
                'stopwords' => true,
                'vader_lexicon' => true,
            ],
        ],
        'features' => [
            'sentiment_analysis' => true,
            'text_summarization' => true,
            'keyword_extraction' => true,
            'language_detection' => true,
            'text_classification' => true,
            'named_entity_recognition' => true,
            'part_of_speech_tagging' => true,
        ],
    ],

    // Computer Vision
    'computer_vision' => [
        'enabled' => $_ENV['CV_ENABLED'] ?? true,
        'features' => [
            'face_detection' => true,
            'face_recognition' => true,
            'object_detection' => true,
            'image_classification' => true,
            'ocr' => true,
            'image_enhancement' => true,
            'video_analysis' => true,
        ],
        'ocr' => [
            'engine' => 'tesseract',
            'languages' => ['eng', 'fra', 'spa', 'deu'],
            'config' => '--psm 6',
        ],
    ],

    // Deep Learning
    'deep_learning' => [
        'enabled' => $_ENV['DL_ENABLED'] ?? true,
        'frameworks' => [
            'tensorflow' => true,
            'pytorch' => $_ENV['PYTORCH_ENABLED'] ?? false,
            'keras' => true,
        ],
        'training' => [
            'batch_size' => $_ENV['DL_BATCH_SIZE'] ?? 32,
            'epochs' => $_ENV['DL_EPOCHS'] ?? 100,
            'learning_rate' => $_ENV['DL_LEARNING_RATE'] ?? 0.001,
            'validation_split' => $_ENV['DL_VALIDATION_SPLIT'] ?? 0.2,
            'early_stopping' => true,
            'patience' => 10,
        ],
        'architectures' => [
            'cnn' => [
                'conv_layers' => 3,
                'filters' => [32, 64, 128],
                'kernel_size' => [3, 3],
                'activation' => 'relu',
                'pooling' => 'max',
            ],
            'rnn' => [
                'units' => 128,
                'return_sequences' => false,
                'dropout' => 0.2,
                'recurrent_dropout' => 0.2,
            ],
            'lstm' => [
                'units' => 128,
                'return_sequences' => false,
                'dropout' => 0.2,
                'recurrent_dropout' => 0.2,
            ],
        ],
    ],

    // Machine Learning Pipelines
    'ml_pipelines' => [
        'data_preprocessing' => [
            'scaling' => 'standard',
            'encoding' => 'one_hot',
            'missing_values' => 'mean',
            'outlier_detection' => 'isolation_forest',
        ],
        'feature_selection' => [
            'method' => 'recursive_feature_elimination',
            'n_features' => 10,
        ],
        'model_selection' => [
            'cross_validation' => 5,
            'scoring' => 'accuracy',
            'grid_search' => true,
        ],
        'evaluation' => [
            'metrics' => ['accuracy', 'precision', 'recall', 'f1_score'],
            'confusion_matrix' => true,
            'roc_curve' => true,
        ],
    ],

    // Recommendation System
    'recommendation' => [
        'enabled' => $_ENV['RECOMMENDATION_ENABLED'] ?? true,
        'algorithms' => [
            'collaborative_filtering' => [
                'method' => 'matrix_factorization',
                'factors' => 50,
                'regularization' => 0.01,
                'iterations' => 100,
            ],
            'content_based' => [
                'similarity' => 'cosine',
                'features' => ['category', 'difficulty', 'duration', 'tags'],
            ],
            'hybrid' => [
                'collaborative_weight' => 0.6,
                'content_weight' => 0.4,
            ],
        ],
        'cold_start' => [
            'strategy' => 'popularity_based',
            'min_interactions' => 5,
        ],
    ],

    // Model Management
    'model_management' => [
        'versioning' => true,
        'auto_backup' => true,
        'compression' => true,
        'encryption' => false,
        'storage' => [
            'local' => STORAGE_PATH . '/ai-models',
            's3' => $_ENV['AI_MODELS_S3_BUCKET'] ?? null,
        ],
        'deployment' => [
            'auto_deploy' => false,
            'staging_environment' => true,
            'rollback_enabled' => true,
        ],
    ],

    // Performance Monitoring
    'monitoring' => [
        'enabled' => $_ENV['AI_MONITORING_ENABLED'] ?? true,
        'metrics' => [
            'prediction_accuracy' => true,
            'response_time' => true,
            'memory_usage' => true,
            'cpu_usage' => true,
            'error_rate' => true,
        ],
        'alerts' => [
            'accuracy_threshold' => 0.8,
            'response_time_threshold' => 5000, // milliseconds
            'error_rate_threshold' => 0.05,
        ],
        'logging' => [
            'predictions' => true,
            'training_logs' => true,
            'error_logs' => true,
        ],
    ],

    // Data Management
    'data' => [
        'formats' => [
            'supported' => ['csv', 'json', 'xlsx', 'parquet', 'hdf5'],
            'preferred' => 'parquet',
        ],
        'validation' => [
            'schema_validation' => true,
            'data_quality_checks' => true,
            'outlier_detection' => true,
        ],
        'preprocessing' => [
            'auto_clean' => true,
            'handle_missing' => 'auto',
            'normalize' => true,
            'feature_engineering' => false,
        ],
        'storage' => [
            'local' => STORAGE_PATH . '/datasets',
            's3' => $_ENV['DATASETS_S3_BUCKET'] ?? null,
            'compression' => 'gzip',
        ],
    ],

    // Security
    'security' => [
        'model_encryption' => false,
        'data_encryption' => true,
        'access_control' => true,
        'audit_logging' => true,
        'secure_inference' => true,
    ],

    // Experimental Features
    'experimental' => [
        'auto_ml' => $_ENV['AUTO_ML_ENABLED'] ?? false,
        'federated_learning' => $_ENV['FEDERATED_LEARNING_ENABLED'] ?? false,
        'quantum_ml' => $_ENV['QUANTUM_ML_ENABLED'] ?? false,
        'explainable_ai' => $_ENV['EXPLAINABLE_AI_ENABLED'] ?? false,
    ],
];
#!/bin/bash
# GCP Deployment Script - Production Ready

set -e

echo "🚀 The Wave Men's Salon - GCP Deployment"
echo "========================================"

# Configuration
PROJECT_ID=${PROJECT_ID:-$(gcloud config get-value project)}
REGION=${REGION:-asia-south1}
SERVICE_NAME=${SERVICE_NAME:-wave-salon}
REPO_NAME=${REPO_NAME:-wave-salon-repo}

# Check prerequisites
check_prerequisites() {
    echo "📋 Checking prerequisites..."
    
    if ! command -v gcloud &> /dev/null; then
        echo "❌ gcloud CLI not found. Please install Google Cloud SDK."
        exit 1
    fi
    
    if ! gcloud auth print-access-token &> /dev/null; then
        echo "❌ Not authenticated. Run: gcloud auth login"
        exit 1
    fi
    
    echo "✅ Prerequisites OK"
}

# Enable required APIs
enable_apis() {
    echo "🔧 Enabling required APIs..."
    
    gcloud services enable \
        artifactregistry.googleapis.com \
        cloudbuild.googleapis.com \
        run.googleapis.com \
        sqladmin.googleapis.com \
        secretmanager.googleapis.com \
        storage.googleapis.com \
        cloudscheduler.googleapis.com \
        --project=$PROJECT_ID
    
    echo "✅ APIs enabled"
}

# Create Artifact Registry
create_artifact_registry() {
    echo "📦 Creating Artifact Registry..."
    
    if ! gcloud artifacts repositories describe $REPO_NAME --location=$REGION --project=$PROJECT_ID &> /dev/null; then
        gcloud artifacts repositories create $REPO_NAME \
            --repository-format=docker \
            --location=$REGION \
            --project=$PROJECT_ID \
            --description="The Wave Men's Salon Docker Repository"
    fi
    
    echo "✅ Artifact Registry ready"
}

# Create Cloud SQL instance
create_cloud_sql() {
    echo "🗄️ Creating Cloud SQL instance..."
    
    INSTANCE_NAME="wave-salon-db"
    
    if ! gcloud sql instances describe $INSTANCE_NAME --project=$PROJECT_ID &> /dev/null; then
        gcloud sql instances create $INSTANCE_NAME \
            --database-version=MYSQL_8_0 \
            --cpu=2 \
            --memory=4GB \
            --region=$REGION \
            --root-password=$(openssl rand -base64 32) \
            --backup-start-time=02:00 \
            --enable-bin-log \
            --storage-auto-increase \
            --project=$PROJECT_ID
        
        # Create database
        gcloud sql databases create wave_salon --instance=$INSTANCE_NAME --project=$PROJECT_ID
    fi
    
    echo "✅ Cloud SQL ready"
}

# Create Cloud Storage bucket
create_storage_bucket() {
    echo "📁 Creating Cloud Storage bucket..."
    
    BUCKET_NAME="wave-salon-media-${PROJECT_ID}"
    
    if ! gsutil ls gs://$BUCKET_NAME &> /dev/null; then
        gsutil mb -l $REGION gs://$BUCKET_NAME
        gsutil iam ch allUsers:objectViewer gs://$BUCKET_NAME
    fi
    
    echo "✅ Storage bucket ready"
}

# Create Secret Manager secrets
create_secrets() {
    echo "🔐 Setting up Secret Manager..."
    
    secrets=(
        "DB_PASSWORD:$(openssl rand -base64 32)"
        "APP_KEY:base64:$(openssl rand -base64 32)"
        "STRIPE_SECRET_KEY:sk_test_placeholder"
        "TWILIO_TOKEN:placeholder"
    )
    
    for secret in "${secrets[@]}"; do
        key=${secret%:*}
        value=${secret#*:}
        
        if ! gcloud secrets describe $key --project=$PROJECT_ID &> /dev/null; then
            echo -n $value | gcloud secrets create $key \
                --data-file=- \
                --project=$PROJECT_ID \
                --replication-policy=automatic
        fi
    done
    
    echo "✅ Secrets ready"
}

# Deploy to Cloud Run
deploy_cloud_run() {
    echo "🚀 Deploying to Cloud Run..."
    
    gcloud builds submit --config=cloudbuild.yaml \
        --project=$PROJECT_ID \
        --substitutions=_REGION=$REGION,_SERVICE=$SERVICE_NAME,_REPO=$REPO_NAME
    
    echo "✅ Deployment complete"
}

# Create Cloud Scheduler for reports
create_scheduler() {
    echo "⏰ Setting up Cloud Scheduler..."
    
    gcloud scheduler jobs create pubsub daily-report \
        --schedule="0 9 * * *" \
        --topic=report-generation \
        --message-body='{"type":"daily_report"}' \
        --location=$REGION \
        --project=$PROJECT_ID || true
    
    echo "✅ Scheduler ready"
}

# Main deployment
main() {
    echo ""
    echo "Starting deployment process..."
    echo ""
    
    check_prerequisites
    enable_apis
    create_artifact_registry
    create_cloud_sql
    create_storage_bucket
    create_secrets
    deploy_cloud_run
    create_scheduler
    
    echo ""
    echo "🎉 Deployment complete!"
    echo ""
    echo "📍 Service URL: https://${SERVICE_NAME}-${PROJECT_ID}.${REGION}.run.app"
    echo ""
    echo "📝 Next steps:"
    echo "1. Set up your custom domain in Cloud Run"
    echo "2. Update STRIPE_SECRET_KEY in Secret Manager"
    echo "3. Configure your database backup schedule"
    echo "4. Set up monitoring alerts"
}

main
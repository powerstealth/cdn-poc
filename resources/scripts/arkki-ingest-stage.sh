#!/bin/bash

# Variables
REMOTE="clyup-stage:clyup-staging-media/arkki"  # Remote path
LOCAL_DIR="/mnt/arkkimount/INGEST/"             # Local directory to save files for the ingest

# Ensure the local directory exists
mkdir -p "$LOCAL_DIR"

# Sync files from the remote directory to the local directory
echo "Starting download from $REMOTE to $LOCAL_DIR..."
rclone sync "$REMOTE" "$LOCAL_DIR" --progress

# Check if the sync command succeeded
if [ $? -eq 0 ]; then
    echo "Files downloaded successfully to $LOCAL_DIR"

    # Remove files inside the remote directory
    echo "Removing files from $REMOTE..."
    rclone delete "$REMOTE" --progress

    # Remove any empty directories inside the remote directory, but keep the root
    echo "Cleaning up empty directories in $REMOTE..."
    rclone rmdirs "$REMOTE" --leave-root --progress

    # Check if the delete and rmdirs commands succeeded
    if [ $? -eq 0 ]; then
        echo "Files removed successfully from $REMOTE."
    else
        echo "Error: Failed to remove files or clean up $REMOTE"
        exit 2
    fi
else
    echo "Error: Failed to download files from $REMOTE"
    exit 1
fi
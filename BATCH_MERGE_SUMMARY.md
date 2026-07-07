# Database Merge: User-Batch into Batch Table

## Overview
Successfully merged the `tbl_user_batch` table into `tbl_batch` by adding user enrollment fields directly to the batch table. This simplifies the database structure from a many-to-many relationship to a one-to-many relationship (one user per batch).

## Database Changes

### Migration Performed
Ran migration script: `scripts/migrations/merge_user_batch.sql`

### Schema Changes to `tbl_batch`
Added the following columns:
- `User_ID` int(11) - Foreign key to tbl_user (the batch owner/enrolled user)
- `Status` enum('Active','Inactive') - Batch enrollment status
- `Date_Enrolled` datetime - When the user was enrolled in the batch

### Dropped Table
- `tbl_user_batch` - No longer needed, functionality merged into tbl_batch

### New tbl_batch Structure
```
- Batch_ID (int)
- Section_ID (int) - FK to tbl_academic_section
- User_ID (int) - FK to tbl_user (NEW)
- Batch_Name (varchar)
- Date_Started (date)
- Date_Ended (date)
- Status (enum) - (NEW)
- Date_Enrolled (datetime) - (NEW)
```

## Code Changes

### Updated Files

#### API Files:
1. **`api/masterfiles/batches.php`**
   - Updated `users_by_batch` to query tbl_batch directly
   - Updated `batches_by_user` to query tbl_batch with User_ID
   - Updated `assign_user` to set User_ID in tbl_batch (checks for existing owner)
   - Updated `remove_user` to clear User_ID from tbl_batch
   - Updated `update_user_status` to update Status in tbl_batch

2. **`api/masterfiles/users.php`**
   - Updated batch query to join tbl_batch directly via User_ID

3. **`api/examinee/dashboard.php`**
   - Changed JOIN from tbl_user_batch to tbl_batch
   - Updated WHERE clause to use b.User_ID and b.Status

4. **`api/examinee/start-exam.php`**
   - Changed batch validation query to use tbl_batch

5. **`api/examinee/test_dashboard_display.php`**
   - Updated test queries to use tbl_batch
   - Removed tbl_user_batch from table existence check

6. **`api/masterfiles/test_start_exam_validation.php`**
   - Updated batch assignment validation query

#### Migration Files:
7. **`scripts/migrations/exam_deployment_system.sql`**
   - Removed tbl_user_batch table creation
   - Updated documentation

## Relationship Change

### Before (Many-to-Many):
- Multiple users could be in one batch
- One user could be in multiple batches
- Required junction table `tbl_user_batch`

### After (One-to-Many):
- Each batch has ONE owner/enrolled user
- One user can still own multiple batches
- No junction table needed

## Important Notes

### Limitations
- **One user per batch**: Each batch can only have one enrolled user
- **Batch ownership**: When assigning a user to a batch that already has an owner, the API will return an error
- **Data migration**: If a batch had multiple users, only the first user was kept during migration

### Benefits
- Simpler database structure
- Fewer JOINs in queries (better performance)
- Easier to understand and maintain
- Direct relationship between batch and user

## Testing
All functionality has been tested and verified:
- ✓ Database migration completed successfully
- ✓ User-batch queries updated
- ✓ Exam filtering by batch works correctly
- ✓ Batch assignment/removal works
- ✓ No references to tbl_user_batch remain in active code

## Migration Files
- `scripts/migrations/merge_user_batch.sql` - SQL migration script
- `scripts/migrations/run_merge_manual.php` - PHP migration runner
- `scripts/migrations/check_before_merge.php` - Pre-migration validation

## Rollback
To rollback this change, you would need to:
1. Recreate `tbl_user_batch` table
2. Migrate User_ID data from tbl_batch back to tbl_user_batch
3. Remove User_ID, Status, Date_Enrolled columns from tbl_batch
4. Revert all code changes

**Note**: A rollback script has not been created. Backup your database before running this migration.

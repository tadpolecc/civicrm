{* file to handle db changes in 5.47.1 during upgrade *}

-- Remove entry for Grant Reports since grant is not a component anymore so this url just shows an empty list
DELETE FROM civicrm_navigation WHERE url='civicrm/report/list?compid=5&reset=1';

DELETE FROM civicrm_managed WHERE module = "civigrant" AND entity_type = "OptionValue"
AND name LIKE "OptionGroup_grant_type_%";

{* Revert a90c3874d6ea74017dd038cc64d952afe99a1981 *}
ALTER TABLE `civicrm_event`
  MODIFY COLUMN `start_date` datetime NULL DEFAULT NULL COMMENT 'Date and time that event starts.',
  MODIFY COLUMN `end_date` datetime NULL DEFAULT NULL COMMENT 'Date and time that event ends. May be NULL if no defined end date/time',
  MODIFY COLUMN `registration_start_date` datetime NULL DEFAULT NULL COMMENT 'Date and time that online registration starts.',
  MODIFY COLUMN `registration_end_date` datetime NULL DEFAULT NULL COMMENT 'Date and time that online registration ends.';

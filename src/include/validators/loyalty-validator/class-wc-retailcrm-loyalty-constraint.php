<?php

if (!class_exists('WC_Retailcrm_Loyalty_Constraint')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Loyalty_Constraint - Constraint for CRM loyalty.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Loyalty_Constraint
    {
        public $notFoundCrmUser = 'User not found in the system';

        public $errorFoundLoyalty = 'Error when searching for participation in loyalty programs';

        public $notFoundActiveParticipation = 'No active participation in the loyalty program was detected';

        public $notExistBonuses = 'No bonuses for debiting';

        public $notFoundLoyalty = 'Loyalty program not found';

        public $loyaltyInactive = 'Loyalty program is not active';

        public $loyaltyBlocked = 'Loyalty program blocked';

        public $isCorporateUser = 'This user is a corporate person';
    }
endif;

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    /**
     * This function use to copy fields
     *
     * @param fname string field name
     * @return void
     */
    function copyFieldValues( fname ) {
      if (fname === 'soft_credit_type') {
        var elementId    = $('.crm-copy-fields [name^="soft_credit_type[');
      }
      else {
        // this is the most common pattern for elements, so first check if it exits
        // this check field starting with "field[" and contains [fname] and is not
        // hidden ( for checkbox hidden element is created )
        var elementId = $('.crm-copy-fields [name^="field["][name*="[' + fname + ']"][type!=hidden]');
      }

      // get the first element and it's value
      var firstElement = elementId.eq(0);
      var firstElementValue = firstElement.val();

      //check if it is date element
      var isDateElement     = elementId.attr('format');

      //get the element type
      var elementType       = elementId.attr('type');

      // set the value for all the elements, elements needs to be handled are
      // select, checkbox, radio, date fields, text, textarea, multi-select
      // wysiwyg editor, advanced multi-select ( to do )
      if ( elementType == 'radio' ) {
        var firstElementId    = $('.crm-copy-fields tr:first-child [name^="field["][name*="[' + fname +']"][type!=hidden]');
        firstElementValue = firstElementId.filter(':checked').eq(0).val();
        // if radio button is uncheck then unset all the fields.
        if (typeof firstElementValue == 'undefined') {
          elementId.prop("checked", false).change().siblings('a.crm-clear-link').trigger('click');
        }
        else {
          elementId.filter("[value='" + firstElementValue + "']").prop("checked", true).change();
        }
      }
      else if ( elementType == 'checkbox' ) {
        // handle checkbox
        // get the entity id of first element
        var firstEntityId = $('.crm-copy-fields > tbody > tr');

        if ( firstEntityId.length == 0 ) {
          firstEntityId = firstElement.closest('div.crm-grid-row');
        }

        firstEntityId = firstEntityId.attr('entity_id');

        var firstCheckElement = $('.crm-copy-fields [type=checkbox][name^="field['+ firstEntityId +']['+ fname +']"][type!=hidden]');

        if ( firstCheckElement.length > 1 ) {
          // lets uncheck all the checkbox except first one
          $('.crm-copy-fields [type=checkbox][name^="field["][name*="[' + fname +']"][type=checkbox]:not([name^="field['+ firstEntityId +']['+ fname +']["])').prop('checked', false);

          //here for each checkbox for first row, check if it is checked and set remaining checkboxes
          firstCheckElement.each(function() {
            if ($(this).prop('checked') ) {
              var elementName = $(this).attr('name');
              var correctIndex = elementName.split('field['+ firstEntityId +']['+ fname +'][');
              correctIndexValue = correctIndex[1].replace(']', '');
              $('.crm-copy-fields [type=checkbox][name^="field["][name*="['+ fname +']['+ correctIndexValue+']"][type!=hidden]').prop('checked',true).change();
            }
          });
        }
        else {
          if ( firstCheckElement.prop('checked') ) {
            $('.crm-copy-fields [type=checkbox][name^="field["][name*="['+ fname +']"][type!=hidden]').prop('checked',true).change();
          }
          else {
            $('.crm-copy-fields [type=checkbox][name^="field["][name*="['+ fname +']"][type!=hidden]').prop('checked', false).change();
          }
        }
      }
      else if (elementId.is('textarea')) {
        var text = CRM.wysiwyg.getVal(firstElement);
        elementId.each(function() {
          CRM.wysiwyg.setVal(this, text);
        });
      }
      else {
        if (elementId.is('select') === true && firstElement.parent().find(':input').select().index() >= 1 && firstElement.parent().find('select').select().length > 1) {
          // its a multiselect case
          firstElement.parent().find(':input').select().each( function(count) {
            var firstElementValue = $(this).val();
            var elementId = $('.crm-copy-fields [name^="field["][name*="[' + fname +'][' + count + '"][type!=hidden]');
            elementId.val(firstElementValue).not(":first").change();
          });
        }
        else {
          elementId.val(firstElementValue).change();
        }
      }

      // since we use different display field for date we also need to set it.
      // also check for date time field and set the value correctly
      if ( isDateElement ) {
        copyValuesDate( fname );
      }
    }

    /**
     * Special function to handle setting values for date fields
     *
     * @param fname string field name
     * @return void
     */
    function copyValuesDate(fname) {
      var displayElement = $('.crm-copy-fields [name^="field_"][name*="_' + fname +'_display"]:visible');
      var timeElement    = $('.crm-copy-fields [name^="field["][name*="[' + fname +'_time]"][type!=hidden]');

      displayElement.val( displayElement.eq(0).val() );
      timeElement.val( timeElement.eq(0).val() );
    }

    //bind the click event for action icon
    $('.action-icon').click(function( ) {
      copyFieldValues($(this).attr('fname'));
    });
  });


</script>
{/literal}

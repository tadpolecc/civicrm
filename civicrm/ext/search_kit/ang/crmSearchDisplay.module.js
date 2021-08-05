(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchDisplay', CRM.angRequires('crmSearchDisplay'))

    .factory('searchDisplayUtils', function(crmApi4) {

      // Replace tokens keyed to rowData.
      // If rowMeta is provided, values will be formatted; if omitted, raw values will be provided.
      function replaceTokens(str, rowData, rowMeta, index) {
        if (!str) {
          return '';
        }
        _.each(rowData, function(value, key) {
          if (str.indexOf('[' + key + ']') >= 0) {
            var column = rowMeta && _.findWhere(rowMeta, {key: key}),
              val = column ? formatRawValue(column, value) : value,
              replacement = angular.isArray(val) ? val[index || 0] : val;
            str = str.replace(new RegExp(_.escapeRegExp('[' + key + ']', 'g')), replacement);
          }
        });
        return str;
      }

      function getUrl(link, rowData, index) {
        var url = replaceTokens(link, rowData, null, index);
        if (url.slice(0, 1) !== '/' && url.slice(0, 4) !== 'http') {
          url = CRM.url(url);
        }
        return url;
      }

      // Returns display value for a single column in a row
      function formatDisplayValue(rowData, key, columns) {
        var column = _.findWhere(columns, {key: key}),
          displayValue = column.rewrite ? replaceTokens(column.rewrite, rowData, columns) : formatRawValue(column, rowData[key]);
        return angular.isArray(displayValue) ? displayValue.join(', ') : displayValue;
      }

      // Returns value and url for a column formatted as link(s)
      function formatLinks(rowData, key, columns) {
        var column = _.findWhere(columns, {key: key}),
          value = formatRawValue(column, rowData[key]),
          values = angular.isArray(value) ? value : [value],
          links = [];
        _.each(values, function(value, index) {
          links.push({
            value: value,
            url: getUrl(column.link.path, rowData, index)
          });
        });
        return links;
      }

      // Formats raw field value according to data type
      function formatRawValue(column, value) {
        var type = column && column.dataType,
          result = value;
        if (_.isArray(value)) {
          return _.map(value, function(val) {
            return formatRawValue(column, val);
          });
        }
        if (value && (type === 'Date' || type === 'Timestamp') && /^\d{4}-\d{2}-\d{2}/.test(value)) {
          result = CRM.utils.formatDate(value, null, type === 'Timestamp');
        }
        else if (type === 'Boolean' && typeof value === 'boolean') {
          result = value ? ts('Yes') : ts('No');
        }
        else if (type === 'Money' && typeof value === 'number') {
          result = CRM.formatMoney(value);
        }
        return result;
      }

      function getApiParams(ctrl, mode) {
        return {
          return: mode || 'page:' + ctrl.page,
          savedSearch: ctrl.search,
          display: ctrl.display,
          sort: ctrl.sort,
          filters: _.assign({}, (ctrl.afFieldset ? ctrl.afFieldset.getFieldData() : {}), ctrl.filters),
          afform: ctrl.afFieldset ? ctrl.afFieldset.getFormName() : null
        };
      }

      function getResults(ctrl) {
        return crmApi4('SearchDisplay', 'run', getApiParams(ctrl)).then(function(results) {
          ctrl.results = results;
          ctrl.editing = false;
          if (!ctrl.rowCount) {
            if (!ctrl.settings.limit || results.length < ctrl.settings.limit) {
              ctrl.rowCount = results.length;
            } else if (ctrl.settings.pager) {
              var params = getApiParams(ctrl, 'row_count');
              crmApi4('SearchDisplay', 'run', params).then(function(result) {
                ctrl.rowCount = result.count;
              });
            }
          }
        });
      }

      return {
        formatDisplayValue: formatDisplayValue,
        formatLinks: formatLinks,
        getApiParams: getApiParams,
        getResults: getResults,
        replaceTokens: replaceTokens,
        getUrl: getUrl
      };
    });

})(angular, CRM.$, CRM._);

CDash.controller('ViewTestController',
  function ViewTestController($scope, $rootScope, $http) {
    $scope.loading = true;
    // Hide filters by default.
    $scope.showfilters = false;
    $http({
      url: 'api/v1/viewTest.php',
      method: 'GET',
      params: $rootScope.queryString
    }).success(function(cdash) {
      $scope.cdash = cdash;
      // Set title in root scope so the head controller can see it.
      $rootScope['title'] = cdash.title;
    }).finally(function() {
      $scope.loading = false;
    });
});

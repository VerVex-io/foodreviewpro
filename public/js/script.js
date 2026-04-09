// Initialize Lucide Icons (only if loaded)
if (typeof lucide !== 'undefined') lucide.createIcons();

// Dashboard Navigation
document.addEventListener('DOMContentLoaded', function() {
  // Initialize date pickers
  if (typeof flatpickr !== 'undefined' && flatpickr.l10ns)
    flatpickr.localize(flatpickr.l10ns.fr);

  // Sidebar toggle for mobile
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.querySelector('main');

  sidebarToggle.addEventListener('click', function() {
    sidebar.classList.toggle('-translate-x-full');
  });
});

// Initialize Charts
initCharts();

// Sample data for demonstration
initSampleData();

// Initialize Charts
function initCharts() {
  // Chart 1: Reviews by Category (Bar Chart)
  const chart1Options = {
    series: [{name: 'Nombre d\'avis', data: [45, 52, 38, 24, 33, 42, 28]}],
    chart: {type: 'bar', height: 300, toolbar: {show: false}},
    colors: ['#3b82f6'],
    plotOptions: {
      bar: {
        borderRadius: 4,
        horizontal: false,
      }
    },
    dataLabels: {enabled: false},
    xaxis: {
      categories: [
        'Français', 'Italien', 'Asiatique', 'Fast Food', 'Végétarien',
        'Brasserie', 'Autre'
      ]
    },
    yaxis: {title: {text: 'Nombre d\'avis'}}
  };

  const chart1 =
      new ApexCharts(document.querySelector('#chart1'), chart1Options);
  chart1.render();

  // Chart 2: Rating Evolution (Line Chart)
  const chart2Options = {
    series: [{name: 'Note moyenne', data: [4.1, 4.2, 4.3, 4.2, 4.4, 4.3, 4.5]}],
    chart: {
      height: 300,
      type: 'line',
      zoom: {enabled: false},
      toolbar: {show: false}
    },
    colors: ['#8b5cf6'],
    dataLabels: {enabled: false},
    stroke: {curve: 'smooth', width: 3},
    grid: {
      row: {colors: ['#f3f3f3', 'transparent'], opacity: 0.5},
    },
    xaxis: {categories: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil']},
    yaxis: {min: 3, max: 5}
  };

  const chart2 =
      new ApexCharts(document.querySelector('#chart2'), chart2Options);
  chart2.render();

  // Chart 3: Distribution by Category (Donut Chart)
  const chart3Options = {
    series: [42, 35, 15, 5, 3],
    chart: {type: 'donut', height: 300},
    colors: ['#10b981', '#8b5cf6', '#3b82f6', '#f59e0b', '#ef4444'],
    labels: ['5 étoiles', '4 étoiles', '3 étoiles', '2 étoiles', '1 étoile'],
    legend: {position: 'bottom'},
    responsive: [{
      breakpoint: 480,
      options: {chart: {width: 200}, legend: {position: 'bottom'}}
    }]
  };

  const chart3 =
      new ApexCharts(document.querySelector('#chart3'), chart3Options);
  chart3.render();
}

// Sample data initialization
function initSampleData() {
  // Create some sample restaurant data
  const sampleRestaurants = [
    {
      id: 1,
      name: 'Le Bistrot Parisien',
      category: 'Français',
      rating: 4.2,
      reviews: 142,
      city: 'Paris',
      status: 'active',
      image: 'http://static.photos/restaurant/200x200/1'
    },
    {
      id: 2,
      name: 'La Table Gourmande',
      category: 'Français',
      rating: 4.8,
      reviews: 256,
      city: 'Paris',
      status: 'active',
      image: 'http://static.photos/restaurant/200x200/3'
    },
    {
      id: 3,
      name: 'Trattoria Bella',
      category: 'Italien',
      rating: 4.5,
      reviews: 89,
      city: 'Lyon',
      status: 'active',
      image: 'http://static.photos/restaurant/200x200/4'
    }
  ];

  // Create sample reviews
  const sampleReviews = [
    {
      id: 1,
      user: 'Sophie Martin',
      restaurant: 'Le Bistrot Parisien',
      rating: 4.0,
      comment:
          'Excellente expérience ! Le service était impeccable et les plats délicieux. Je recommande vivement la côte de bœuf.',
      date: 'Il y a 2h',
      status: 'pending',
      userImage: 'http://static.photos/people/200x200/2'
    },
    {
      id: 2,
      user: 'Pierre Dubois',
      restaurant: 'La Table Gourmande',
      rating: 5.0,
      comment:
          'Une expérience gastronomique exceptionnelle. Le chef a su nous surprendre avec des créations originales.',
      date: 'Il y a 5h',
      status: 'approved',
      userImage: 'http://static.photos/people/200x200/3'
    }
  ];

  // Store in localStorage for demo purposes
  localStorage.setItem('restaurants', JSON.stringify(sampleRestaurants));
  localStorage.setItem('reviews', JSON.stringify(sampleReviews));

  // Initialize search functionality
  initSearch();
}

// Search functionality
function initSearch() {
  const searchInput =
      document.querySelector('input[placeholder*="Rechercher"]');

  searchInput.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();

    if (searchTerm.length > 2) {
      // In a real app, this would be an API call
      console.log('Searching for:', searchTerm);
      // Show search results
    }
  });
}

// Restaurant management functions
function addRestaurant(restaurantData) {
  const restaurants = JSON.parse(localStorage.getItem('restaurants') || '[]');
  restaurants.push({id: restaurants.length + 1, ...restaurantData});
  localStorage.setItem('restaurants', JSON.stringify(restaurants));
  return true;
}

function updateRestaurant(id, restaurantData) {
  const restaurants = JSON.parse(localStorage.getItem('restaurants') || '[]');
  const index = restaurants.findIndex(r => r.id === id);

  if (index !== -1) {
    restaurants[index] = {...restaurants[index], ...restaurantData};
    localStorage.setItem('restaurants', JSON.stringify(restaurants));
    return true;
  }
  return false;
}

function deleteRestaurant(id) {
  const restaurants = JSON.parse(localStorage.getItem('restaurants') || '[]');
  const filtered = restaurants.filter(r => r.id !== id);
  localStorage.setItem('restaurants', JSON.stringify(filtered));
  return true;
}

// Review management functions
function approveReview(id) {
  const reviews = JSON.parse(localStorage.getItem('reviews') || '[]');
  const review = reviews.find(r => r.id === id);

  if (review) {
    review.status = 'approved';
    localStorage.setItem('reviews', JSON.stringify(reviews));
    return true;
  }
  return false;
}

function deleteReview(id) {
  const reviews = JSON.parse(localStorage.getItem('reviews') || '[]');
  const filtered = reviews.filter(r => r.id !== id);
  localStorage.setItem('reviews', JSON.stringify(filtered));
  return true;
}

// Export data function
function exportData(type) {
  const data = localStorage.getItem(type);
  const blob = new Blob([data], {type: 'application/json'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `${type}_export_${new Date().toISOString().split('T')[0]}.json`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

// Responsive sidebar handling
window.addEventListener('resize', function() {
  if (window.innerWidth >= 1024) {
    document.getElementById('sidebar').classList.remove('-translate-x-full');
  }
});
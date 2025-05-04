<?php
$page_title = '리버티노를 소개합니다';
include 'header.php';
?>

<style>
  /* 기본 스타일 */
  .about-page {
    padding-top: 0;
    text-align: center;
  }
  
  .section {
    margin: 0;
    position: relative;
    overflow: hidden;
    padding: calc(clamp(70px, 12vh, 120px) + 3rem) 0 3rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  
  .section-title {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    position: relative;
    display: inline-block;
  }
  
  .section-title::after {
    content: '';
    position: absolute;
    width: 60px;
    height: 4px;
    background: var(--blue);
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
  }
  
  .section-subtitle {
    color: var(--gray-600);
    font-size: 1.2rem;
    margin-bottom: 2rem;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
  }
  
  .text-content {
    text-align: left;
    margin-bottom: 3rem;
  }
  
  .text-content p {
    margin-bottom: 1.5rem;
    line-height: 1.8;
  }
  
  /* 섹션 구분 스타일 */
  .section:nth-child(even) {
    background-color: #f9f9f9;
  }
  
  .section:nth-child(odd) {
    background-color: #ffffff;
  }
  
  /* 메인 이미지 스타일 */
  .main-image {
    width: 100%;
    height: 480px;
    object-fit: cover;
    border-radius: 15px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
  }
  
  /* 원형 카드 스타일 - 수정됨 */
  .circles-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin: 3rem auto;
    max-width: 1200px;
  }
  
  .circle-card-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .circle-card {
    width: 220px;
    height: 220px;
    border-radius: 50%;
    overflow: hidden;
    position: relative;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    transition: transform 0.4s ease, box-shadow 0.4s ease;
  }
  
  .circle-card:hover {
    transform: translateY(-15px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  }
  
  .circle-bg {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    transition: transform 0.4s ease;
  }
  
  .circle-card:hover .circle-bg {
    transform: scale(1.1);
  }
  
  .circle-content {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: rgba(0, 0, 0, 0.85);
    color: white;
    text-align: center;
    padding: 1.5rem;
    opacity: 0.9;
    transition: opacity 0.4s ease;
    
  }
  
  .circle-card:hover .circle-content {
    opacity: 0.9;
  }
  
  .circle-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.7rem;
  }
  
  .circle-text {
    font-size: 0.9rem;
    line-height: 1.5;
    max-width: 100%;
  }
  
  /* 현대적인 세로형 카드 */
  .features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin: 3rem auto;
    max-width: 1200px;
  }
  
  .feature-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  
  .feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
  }
  
  .feature-image {
    height: 0;
    padding-top: 66.67%; /* 2:3 비율 유지 */
    position: relative;
  }
  
  .feature-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
  }
  
  .feature-card:hover .feature-image img {
    transform: scale(1.05);
  }
  
  .feature-content {
    padding: 1.5rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
  }
  
  .feature-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--gray-900);
  }
  
  .feature-text {
    color: var(--gray-600);
    line-height: 1.6;
    text-align: left;
  }
  
  /* 통계 섹션 */
  .stats-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    margin: 4rem auto;
    max-width: 1200px;
  }
  
  .stat-card {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
  }
  
  .stat-card:hover {
    transform: translateY(-10px);
  }
  
  .stat-number {
    font-size: 3rem;
    font-weight: 800;
    color: var(--blue);
    margin-bottom: 0.5rem;
  }
  
  .stat-label {
    color: var(--gray-600);
    font-size: 1.1rem;
  }
  
  /* 가입 섹션 */
  .join-section {
    background: #f0f7ff;
    padding: 5rem 0;
    border-radius: 0;
    margin-top: 0;
    text-align: center;
  }
  
  .join-container {
    max-width: 1000px;
    margin: 0 auto;
  }
  
  .join-image {
    max-width: 500px;
    margin: 0 auto 2rem;
  }
  
  .join-image img {
    width: 100%;
    aspect-ratio: 5 / 4;
    object-fit: cover;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  }
  
  .join-title {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    color: var(--gray-900);
  }
  
  .join-text {
    color: var(--gray-600);
    line-height: 1.7;
    margin-bottom: 2rem;
    text-align: left;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
  }
  
  .join-text p {
    margin-bottom: 1rem;
  }
  
  .join-button {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: var(--blue);
    color: white;
    border-radius: 50px;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.3s ease, background 0.3s ease;
    box-shadow: 0 10px 20px rgba(0, 100, 255, 0.2);
    margin-top: 1rem;
    width: 100%;
    max-width: 800px;
  }
  
  .join-button:hover {
    transform: translateY(-5px);
    background: #0052cc;
    box-shadow: 0 15px 25px rgba(0, 100, 255, 0.3);
  }
  
  /* 반응형 조정 */
  @media (max-width: 1200px) {
    .circle-card {
      width: 200px;
      height: 200px;
    }
  }
  
  @media (max-width: 992px) {
    .circles-container {
      grid-template-columns: repeat(2, 1fr);
    }
    
    .features-grid {
      grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-container {
      grid-template-columns: repeat(2, 1fr);
    }
    
    .circle-card {
      width: 220px;
      height: 220px;
    }
  }
  
  @media (max-width: 768px) {
    .section {
      padding-top: calc(clamp(70px, 12vh, 120px) + 2rem);
    }
    
    .circles-container {
      grid-template-columns: 1fr;
    }
    
    .features-grid {
      grid-template-columns: 1fr;
    }
    
    .stats-container {
      grid-template-columns: 1fr;
    }
    
    .circle-card {
      width: 240px;
      height: 240px;
    }
  }
  
  @media (max-width: 480px) {
    .circle-card {
      width: 200px;
      height: 200px;
    }
  }
</style>

  <!-- 메인 컨텐츠 시작 -->
<main class="about-page">
  <!-- 소개 섹션 -->
  <section id="about" class="section">
    <div class="container">
      <h2 class="section-title">리버티노 오케스트라</h2>
      <p class="section-subtitle">음악으로 함께 성장하는 우리의 이야기</p>
      
      <!-- 메인 이미지 -->
      <img src="../img/about/mainimg.jpg" alt="리버티노 오케스트라 단체 사진" class="main-image">
      
      <div class="text-content">
        <p>리버티노 오케스트라는 <strong>'음악을 사랑하는, 음악을 나누는'</strong> 순천향대학교의 유일한 순수음악 동아리로서, 2005년 창단 이래 음악에 대한 열정 하나로 모인 학생들이 주축이 되어 성장해왔습니다. '리버티노(Libertinus)'라는 이름에는 '자유로운'이라는 의미가 담겨있으며, 다양한 전공과 배경을 가진 학생들이 음악이라는 공통된 언어로 소통하고 함께 성장하는 공간을 상징합니다.</p>
        
        <p>우리 오케스트라는 단순히 음악적 기술을 연마하는 데 그치지 않고, 대학 공동체 내에서 음악을 통해 소통하며 서로의 문화를 이해하고 성장하는 것을 목표로 합니다. 매년 정기연주회, 가을 앙상블 공연, 홈커밍 데이, 동아리 박람회 등 다양한 공연과 활동을 통해 구성원들은 자신의 음악적 재능을 발휘하고, 관객과 함께 음악의 기쁨을 나눕니다. 이러한 활동들은 단원들에게 무대 경험을 제공할 뿐만 아니라, 대학 내 문화예술 활성화에도 기여하고 있습니다.</p>
        
        <p>리버티노 오케스트라는 클래식에 기반을 두고 있지만, OST, 재즈, 현대음악 등 다양한 장르를 아우르는 레퍼토리로 관객과 소통하는 무대를 만들기 위해 끊임없이 도전하고 있습니다. 매주 정기 합주를 통해 앙상블 감각을 키우고, 파트별 연습과 악기 멘토링 시스템을 통해 개인 역량을 강화하며, 철야 합주 등의 집중 훈련을 통해 완성도 높은 연주를 위해 노력합니다.</p>
        
        <p>특히 우리 단체는 음악적 전문성뿐만 아니라 단원들 간의 깊은 유대감과 소속감을 중요시합니다. 다양한 학과와 학년의 학생들이 모여 음악이라는 공통된 관심사를 통해 교류하고, 서로의 성장을 응원하는 과정에서 대학 생활의 깊이 있는 경험과 추억을 쌓아갑니다. 이러한 공동체 정신은 연주 실력 향상뿐만 아니라 개인의 사회성과 협동심을 키우는 소중한 자산이 됩니다.</p>
        
        <p>리버티노 오케스트라는 순천향대학교의 문화 예술 영역을 선도하는 공동체로서, 학내 구성원들에게 양질의 클래식 음악 경험을 선사할 뿐만 아니라, 지역사회와의 연계를 통해 대학의 사회적 가치 실현에도 기여하고 있습니다. 앞으로도 계속해서 음악을 통해 더 나은 공동체를 만들어 나가고자 하는 열정을 바탕으로, 함께 연주하며 만들어가는 자유로운 하모니로 더 많은 이들에게 감동과 희망을 전하는 예술 단체로 발전해 나갈 것입니다.</p>
      </div>
      
      <!-- 통계 섹션 -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-number">1978</div>
          <div class="stat-label">창단 연도</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">44년</div>
          <div class="stat-label">활동 역사</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">50+</div>
          <div class="stat-label">단원 수</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">400+</div>
          <div class="stat-label">연간 관객</div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- 가치와 비전 섹션 -->
  <section id="vision" class="section">
    <div class="container">
      <h2 class="section-title">가치와 비전</h2>
      <p class="section-subtitle">음악을 통한 소통과 성장, 우리가 추구하는 가치입니다</p>
      
      <div class="circles-container">
        <div class="circle-card-wrapper">
          <div class="circle-card">
            <div class="circle-bg" style="background-image: url('../img/about/1.jpg');"></div>
            <div class="circle-content">
              <h3 class="circle-title">음악적 탁월함</h3>
              <p class="circle-text">지속적인 연습과 도전을 통해 음악적 전문성을 키우고, 수준 높은 연주 역량을 개발합니다.</p>
            </div>
          </div>
        </div>
        
        <div class="circle-card-wrapper">
          <div class="circle-card">
            <div class="circle-bg" style="background-image: url('../img/about/2.jpg');"></div>
            <div class="circle-content">
              <h3 class="circle-title">포용적 공동체</h3>
              <p class="circle-text">다양한 전공과 배경의 학생들이 음악으로 하나 되어 서로 존중하고 성장하는 환경을 만듭니다.</p>
            </div>
          </div>
        </div>
        
        <div class="circle-card-wrapper">
          <div class="circle-card">
            <div class="circle-bg" style="background-image: url('../img/about/3.jpg');"></div>
            <div class="circle-content">
              <h3 class="circle-title">창의적 표현</h3>
              <p class="circle-text">클래식의 전통을 존중하면서도 다양한 장르와 새로운 시도를 통해 음악적 경계를 확장합니다.</p>
            </div>
          </div>
        </div>
        
        <div class="circle-card-wrapper">
          <div class="circle-card">
            <div class="circle-bg" style="background-image: url('../img/about/4.jpg');"></div>
            <div class="circle-content">
              <h3 class="circle-title">문화적 기여</h3>
              <p class="circle-text">대학과 지역사회에 양질의 음악 경험을 제공하며, 문화예술 발전에 기여합니다.</p>
            </div>
          </div>
        </div>
        
        <div class="circle-card-wrapper">
          <div class="circle-card">
            <div class="circle-bg" style="background-image: url('../img/about/5.jpg');"></div>
            <div class="circle-content">
              <h3 class="circle-title">인간사랑</h3>
              <p class="circle-text">순천향대학교의 핵심 가치인 '인간사랑'을 음악을 통해 실천하며, 감동과 치유의 하모니를 전합니다.</p>
            </div>
          </div>
        </div>
        
        <div class="circle-card-wrapper">
          <div class="circle-card">
            <div class="circle-bg" style="background-image: url('../img/about/6.jpg');"></div>
            <div class="circle-content">
              <h3 class="circle-title">전문인 양성</h3>
              <p class="circle-text">순천향 설립 이념에 따라 전문성과 인성을 겸비한 음악인으로 성장할 수 있는 기반을 제공합니다.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- 주요 활동 섹션 -->
  <section id="activities" class="section">
    <div class="container">
      <h2 class="section-title">주요 활동</h2>
      <p class="section-subtitle">음악으로 함께하는 다양한 순간들</p>
      
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-image">
            <img src="../img/about/정기.jpg" alt="정기연주회">
          </div>
          <div class="feature-content">
            <h3 class="feature-title">정기연주회</h3>
            <p class="feature-text">1년간의 음악적 성과를 총결산하는 가장 중요한 무대입니다. 매년 가을에 개최되며, 클래식부터 현대 음악까지 다양한 장르의 곡을 선보입니다. 전체 오케스트라가 참여하여 관객들과 음악적 감동을 나눕니다.</p>
          </div>
        </div>
        
        <div class="feature-card">
          <div class="feature-image">
            <img src="../img/about/앙상블.png" alt="앙상블 공연">
          </div>
          <div class="feature-content">
            <h3 class="feature-title">앙상블 공연</h3>
            <p class="feature-text">소규모 편성으로 진행되는 앙상블 공연은 단원들의 세부적인 음악적 역량을 발휘할 수 있는 기회입니다. 주로 봄학기에 개최되며, 현악, 목관, 금관 등 다양한 편성의 연주를 선보여 단원들의 실내악 감각을 향상시킵니다.</p>
          </div>
        </div>
        
        <div class="feature-card">
          <div class="feature-image">
            <img src="../img/about/고고스.jpg" alt="특별 연주회">
          </div>
          <div class="feature-content">
            <h3 class="feature-title">특별 연주회</h3>
            <p class="feature-text">홈커밍 데이, 대학 축제 등 특별한 행사에서 진행되는 공연입니다. 관객과 더 가깝게 소통하며 대중적인 레퍼토리로 음악의 즐거움을 나눕니다. 이러한 특별 공연은 더 많은 학우들에게 오케스트라를 알리는 좋은 기회가 됩니다.</p>
          </div>
        </div>
        
        <div class="feature-card">
          <div class="feature-image">
            <img src="../img/about/파트연습.png" alt="악기 멘토링">
          </div>
          <div class="feature-content">
            <h3 class="feature-title">악기 멘토링</h3>
            <p class="feature-text">선배 단원들이 후배 단원들에게 악기 연주 기술과 음악적 노하우를 전수하는 프로그램입니다. 파트별 멘토링 세션을 통해 개인 실력 향상을 도모하고, 파트 내 균형 있는 성장을 지원합니다. 이 과정에서 선후배 간 유대감이 형성됩니다.</p>
          </div>
        </div>
        
        <div class="feature-card">
          <div class="feature-image">
            <img src="../img/about/MT.jpg" alt="단체 MT">
          </div>
          <div class="feature-content">
            <h3 class="feature-title">화합의 시간</h3>
            <p class="feature-text">MT 및 단합 활동은 음악 외적인 소통과 교류의 장입니다. 학기 초에 주로 진행되며, 신입 단원들의 적응을 돕고 전체 단원들 간의 유대감을 강화합니다. 함께 게임을 하고, 음식을 나누며, 음악에 대한 이야기를 나누는 소중한 시간입니다.</p>
          </div>
        </div>
        
        <div class="feature-card">
          <div class="feature-image">
            <img src="../img/about/예전.jpg" alt="지역사회 공헌">
          </div>
          <div class="feature-content">
            <h3 class="feature-title">지역사회 공헌</h3>
            <p class="feature-text">대학 내 활동을 넘어 지역사회와 함께하는 다양한 공연 활동을 진행합니다. 지역 행사 초청 연주, 버스킹, 복지시설 방문 연주 등을 통해 음악의 감동을 나누고 대학과 지역사회의 문화적 연결고리 역할을 합니다.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- 가입 안내 섹션 -->
  <section id="join" class="join-section section">
    <div class="container">
      <div class="join-container">
        <div class="join-image">
        </div>
        
        <h2 class="join-title">함께하세요</h2>
        <div class="join-text">
          <p>리버티노 오케스트라는 음악을 사랑하는 열정만 있다면 누구에게나 열려있는 공동체입니다. 음악 전공자가 아니어도, 오케스트라 경험이 없어도, 악기를 배운 지 얼마 되지 않았더라도 괜찮습니다.</p>
          
          <p>단원들은 주 2회의 정기 합주와 다양한 행사 참여를 통해 오케스트라 연주 경험을 쌓게 되며, 선배 단원들의 멘토링을 통해 악기 연주 실력을 향상시킬 수 있습니다.</p>
          
          <p>리버티노 오케스트라에서의 경험은 단순한 음악 활동을 넘어, 평생 간직할 소중한 추억과 인연을 만들어 드립니다. 서로 다른 전공과 배경을 가진 사람들이 음악이라는 하나의 언어로 소통하며 만들어가는 하모니의 아름다움을 함께 경험해보세요.</p>
          
          <p>정기 모집은 매 학기 초 동아리 박람회 기간에 진행되지만, 언제든지 문의하시면 상담해 드립니다. 리버티노 오케스트라와 함께 음악의 감동을 나누고, 잊지 못할 대학 생활의 추억을 만들어보세요.</p>
        </div>
        <a href="contact.php" class="join-button">지원하기</a>
      </div>
    </div>
  </section>
</main>

<?php include 'footer.php'; ?>